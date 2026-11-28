<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Scheduled launches by day, with "Notify me" so visitors come back on launch day. */
class UpcomingController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::upcoming()
            ->whereDate('launch_date', '<=', today()->addDays(60))
            ->with('category')
            ->withCount('launchReminders')
            ->orderBy('launch_date')->orderBy('id')
            ->get();

        $remindedIds = $request->user()
            ? $request->user()->launchReminders()->whereIn('product_id', $products->modelKeys())->pluck('product_id')->flip()->all()
            : [];

        return view('upcoming', [
            'days' => $products->groupBy(fn (Product $p) => $p->launch_date->toDateString()),
            'remindedIds' => $remindedIds,
        ]);
    }
}
