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

        // Before the first launch of the day lands, keep the page useful.
        $yesterday = Product::live()->whereDate('launch_date', today()->subDay())->with($with)->ranked()->limit(10)->get();

        // Fair rotation, launch-day Pro apps first (see Product::featuredRotation).
        $featured = Product::featuredRotation();
        $featuredTotal = Product::featuredPoolSize();

        $week = Product::launchedBetween(today()->startOfWeek(), today())->with($with)->ranked()->limit(5)->get();

        $categories = Category::withCount(['products' => fn ($q) => $q->live()])
            ->get()
            ->where('products_count', '>', 0)
            ->sortByDesc('products_count')
            ->take(12);

        return view('home', compact('today', 'yesterday', 'featured', 'featuredTotal', 'week', 'categories'));
    }

    /** Today's top launches for the homepage banner, refreshed by the browser every ~20s. */
    public function liveToday()
    {
        $top = \Illuminate\Support\Facades\Cache::remember('live-today', 10, fn () => Product::live()
            ->whereDate('launch_date', today())
            ->ranked()
            ->limit(4)
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'tagline' => $p->tagline,
                'logo' => $p->logoUrl(),
                'url' => route('product', $p),
                'votes' => $p->votes_count,
            ])
            ->all());

        return response()->json(['top' => $top])->header('Cache-Control', 'no-store');
    }
}
