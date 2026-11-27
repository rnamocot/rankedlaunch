<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;

class SitemapController extends Controller
{
    public function index()
    {
        $products = Product::live()->select('slug', 'category_id', 'updated_at')->latest('launch_date')->limit(45000)->get();
        $categories = Category::select('id', 'slug', 'updated_at')->whereHas('products', fn ($q) => $q->live())->get();

        // Alternatives pages only where there's something to compare (3+ live products in the category).
        $liveByCategory = Product::live()->selectRaw('category_id, COUNT(*) as n')->groupBy('category_id')->pluck('n', 'category_id');
        $alternatives = $products->filter(fn ($p) => ($liveByCategory[$p->category_id] ?? 0) >= 3);

        return response()
            ->view('sitemap', compact('products', 'categories', 'alternatives'))
            ->header('Content-Type', 'application/xml');
    }
}
