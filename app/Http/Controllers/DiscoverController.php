<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Buyer-side discovery: search, filters, and "alternatives to X" pages. */
class DiscoverController extends Controller
{
    public const SORTS = ['top' => 'Most upvoted', 'new' => 'Newest', 'trending' => 'Trending'];

    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'category' => ['nullable', 'string', 'exists:categories,slug'],
            'pricing' => ['nullable', Rule::in(array_keys(Product::PRICING_TYPES))],
            'sort' => ['nullable', Rule::in(array_keys(self::SORTS))],
        ]);

        $q = trim($filters['q'] ?? '');
        $sort = $filters['sort'] ?? 'top';

        $products = Product::live()->with('category')
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('tagline', 'like', "%{$q}%")
                ->orWhere('description', 'like', "%{$q}%")))
            ->when($filters['category'] ?? null, fn ($query, $slug) => $query->whereHas('category', fn ($c) => $c->where('slug', $slug)))
            ->when($filters['pricing'] ?? null, fn ($query, $pricing) => $query->where('pricing_type', $pricing))
            ->when($sort === 'new', fn ($query) => $query->latest('launch_date')->orderByDesc('votes_count'))
            ->when($sort === 'trending', fn ($query) => $query->whereDate('launch_date', '>=', today()->subDays(30))->ranked())
            ->when($sort === 'top', fn ($query) => $query->ranked())
            ->paginate(24)
            ->withQueryString();

        return view('discover', [
            'products' => $products,
            'categories' => Category::orderBy('sort')->get(),
            'filters' => $filters + ['q' => $q, 'sort' => $sort],
            'sorts' => self::SORTS,
        ]);
    }

    public function alternatives(Product $product)
    {
        abort_unless($product->isLive(), 404);

        $alternatives = Product::live()->with('category')
            ->whereKeyNot($product->id)
            ->where('category_id', $product->category_id)
            ->ranked()
            ->limit(20)
            ->get();

        // Thin pages hurt SEO: send visitors to the product if there's nothing to compare.
        if ($alternatives->count() < 2) {
            return redirect()->route('product', $product);
        }

        return view('alternatives', compact('product', 'alternatives'));
    }
}
