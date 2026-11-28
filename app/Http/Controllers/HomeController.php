<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index()
    {
        $with = ['category'];

        $today = Product::live()->whereDate('launch_date', today())->with($with)->withCount('comments')->ranked()->get();

        // Earlier days, newest first: yesterday's top five, then "Show more" loads older days.
        $yesterday = Product::live()->whereDate('launch_date', today()->subDay())->with($with)->withCount('comments')->ranked()->get();
        $nextDay = self::previousLaunchDay(today()->subDay());

        // Sidebar: what's coming up, with "Notify me".
        $upcoming = Product::upcoming()->with($with)->orderBy('launch_date')->orderBy('id')->limit(5)->get();
        $remindedIds = auth()->check()
            ? auth()->user()->launchReminders()->whereIn('product_id', $upcoming->modelKeys())->pluck('product_id')->flip()->all()
            : [];
        $upcomingTotal = Product::upcoming()->count();
        $topMakers = LeaderboardController::topMakers(today()->startOfMonth(), 3);

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
            'Upvotes today' => Vote::where('created_at', '>=', today())->count(),
            'Makers' => User::has('products')->count(),
        ];

        return view('home', compact('today', 'yesterday', 'nextDay', 'featured', 'featuredTotal', 'week', 'categories', 'pulse', 'heroStats', 'spotlight', 'upcoming', 'upcomingTotal', 'remindedIds', 'topMakers'));
    }

    /** One earlier launch day for the homepage feed's "Show more" button. */
    public function day(string $date): JsonResponse
    {
        $day = Carbon::parse($date)->startOfDay();
        abort_unless($day->lt(today()), 404);

        $products = Product::live()->whereDate('launch_date', $day)->with('category')->withCount('comments')->ranked()->get();
        abort_if($products->isEmpty(), 404);

        $next = self::previousLaunchDay($day);

        return response()->json([
            'html' => view('partials.feed-day', ['day' => $day, 'products' => $products])->render(),
            'next' => $next?->toDateString(),
        ]);
    }

    /** The closest day before $before that had live launches, if any. */
    public static function previousLaunchDay(Carbon $before): ?Carbon
    {
        $date = Product::live()->whereDate('launch_date', '<', $before)->max('launch_date');

        return $date ? Carbon::parse($date)->startOfDay() : null;
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

        $latest = Vote::with('product:id,name,slug')
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
        $top = Cache::remember('live-today', 10, fn () => Product::live()
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

        $pulse = Cache::remember('live-pulse', 10, fn () => self::pulse());

        return response()->json(['top' => $top] + $pulse)->header('Cache-Control', 'no-store');
    }
}
