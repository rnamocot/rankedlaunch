<?php

namespace App\Http\Controllers;

use App\Models\Product;

class RankingController extends Controller
{
    public const PERIODS = [
        'today' => 'Today',
        'week' => 'This week',
        'month' => 'This month',
        'year' => 'This year',
        'all-time' => 'All time',
    ];

    public function show(string $period = 'week')
    {
        abort_unless(array_key_exists($period, self::PERIODS), 404);

        $query = match ($period) {
            'today' => Product::launched()->whereDate('launch_date', today()),
            'week' => Product::launchedBetween(today()->startOfWeek(), today()),
            'month' => Product::launchedBetween(today()->startOfMonth(), today()),
            'year' => Product::launchedBetween(today()->startOfYear(), today()),
            'all-time' => Product::launched(),
        };

        $products = $query->with('category')->ranked()->paginate(30);

        return view('rankings', [
            'products' => $products,
            'period' => $period,
            'periods' => self::PERIODS,
        ]);
    }
}
