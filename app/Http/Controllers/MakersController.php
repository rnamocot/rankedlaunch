<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use App\Services\LaunchScheduler;

/** Marketing page for makers: why launch here, what you get, how it works. */
class MakersController extends Controller
{
    public function index(LaunchScheduler $scheduler)
    {
        $live = Product::live();

        $stats = [
            'products' => (clone $live)->count(),
            'votes' => (int) (clone $live)->sum('votes_count'),
            'clicks' => (int) (clone $live)->sum('clicks_count'),
            'makers' => User::whereHas('products')->count(),
        ];

        // Only show numbers once they're impressive enough to help, not hurt.
        $showStats = $stats['products'] >= 25;

        $recent = Product::live()->latest('launch_date')->ranked()->limit(8)->get();

        return view('launch', [
            'stats' => $stats,
            'showStats' => $showStats,
            'recent' => $recent,
        ]);
    }
}
