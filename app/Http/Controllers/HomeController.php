<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $with = ['category'];

        $today = Product::live()->whereDate('launch_date', today())->with($with)->ranked()->get();

        // Tomorrow's lineup: approved launches scheduled for tomorrow (shown as a teaser, no links yet).
        $tomorrow = Product::with($with)->where('status', 'approved')->whereDate('launch_date', today()->addDay())->orderBy('id')->get();

        // Before the first launch of the day lands, keep the page useful.
        $yesterday = Product::live()->whereDate('launch_date', today()->subDay())->with($with)->ranked()->limit(20)->get();

        // Fair rotation, launch-day Pro apps first (see Product::featuredRotation).
        $featured = Product::featuredRotation();
        $featuredTotal = Product::featuredPoolSize();

        $week = Product::launchedBetween(today()->startOfWeek(), today())->with($with)->ranked()->limit(5)->get();

        $categories = Category::withCount(['products' => fn ($q) => $q->live()])
            ->get()
            ->where('products_count', '>', 0)
            ->sortByDesc('products_count')
            ->take(12);

        $pulse = self::pulse();

        // Small live numbers beside the hero.
        // The app riding the hero graph: a featured Pro launch (fair rotation), else today's #1.
        $spotlightProduct = Product::featuredRotation(1)->first();
        $spotlight = $spotlightProduct
            ? ['product' => $spotlightProduct, 'label' => 'Featured', 'accent' => true]
            : ($today->first() ? ['product' => $today->first(), 'label' => '#1 today', 'accent' => false] : null);

        $heroStats = [
            'Launching today' => $today->count(),
            'Upvotes today' => \App\Models\Vote::where('created_at', '>=', today())->count(),
            'Makers' => \App\Models\User::has('products')->count(),
        ];

        return view('home', compact('today', 'yesterday', 'featured', 'featuredTotal', 'week', 'categories', 'pulse', 'heroStats', 'spotlight', 'tomorrow'));
    }

    /**
     * Activity around today's launches for the banner: today's categories with counts,
     * and the latest upvotes (product and time only, never who voted).
     */
    public static function pulse(): array
    {
        $todayIds = Product::live()->whereDate('launch_date', today())->pluck('id');

        $categories = Product::whereIn('id', $todayIds)->whereNotNull('category_id')
            ->with('category')->get()
            ->groupBy('category_id')
            ->map(fn ($items) => [
                'name' => $items->first()->category->name,
                'url' => route('category', $items->first()->category),
                'count' => $items->count(),
            ])
            ->sortByDesc('count')->values()->take(8)->all();

        $latest = \App\Models\Vote::with('product:id,name,slug')
            ->whereIn('product_id', $todayIds)
            ->latest()->limit(6)->get()
            ->filter(fn ($v) => $v->product)
            ->map(fn ($v) => [
                'name' => $v->product->name,
                'url' => route('product', $v->product),
                'ago' => $v->created_at->diffForHumans(short: true),
            ])->values()->all();

        return ['categories' => $categories, 'latest' => $latest];
    }

    /** Today's top launches for the homepage banner, refreshed by the browser every ~20s. */
    public function liveToday()
    {
        $top = \Illuminate\Support\Facades\Cache::remember('live-today', 10, fn () => Product::live()
            ->with('category')
            ->whereDate('launch_date', today())
            ->ranked()
            ->limit(5)
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'tagline' => $p->tagline,
                'category' => $p->category?->name,
                'logo' => $p->logoUrl(),
                'url' => route('product', $p),
                'votes' => $p->votes_count,
            ])
            ->all());

        $pulse = \Illuminate\Support\Facades\Cache::remember('live-pulse', 10, fn () => self::pulse());

        return response()->json(['top' => $top] + $pulse)->header('Cache-Control', 'no-store');
    }
}
