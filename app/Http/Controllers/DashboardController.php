<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\LaunchScheduler;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $products = $request->user()->products()->with('category')->latest()->get();

        return view('dashboard', [
            'products' => $products,
            'justPaid' => $request->query('paid'),
        ]);
    }

    /** Launch hub: what happens next, launch-day kit, and results for one product. */
    public function show(Request $request, Product $product, LaunchScheduler $scheduler)
    {
        abort_unless($request->user()->id === $product->user_id || $request->user()->is_admin, 403);

        $product->load('category');

        return view('launch-hub', [
            'product' => $product,
            'dailyRank' => $product->dailyRank(),
            'weeklyRank' => $product->weeklyRank(),
            'daysUntilLaunch' => $product->launch_date ? (int) today()->diffInDays($product->launch_date, false) : null,
            'justPaid' => $request->boolean('paid'),
        ]);
    }
}
