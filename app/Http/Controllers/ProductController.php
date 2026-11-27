<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function show(Request $request, Product $product)
    {
        $user = $request->user();
        $isOwner = $user && ($user->id === $product->user_id || $user->is_admin);

        // Unlaunched products are only visible to their owner and admins.
        abort_unless($product->isLive() || $isOwner, 404);

        if ($product->isLive() && ! $isOwner) {
            $this->countView($request, $product);
        }

        $product->load('category', 'user');

        $related = Product::live()
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->ranked()
            ->limit(4)
            ->get();

        return view('product', [
            'product' => $product,
            'isOwner' => $isOwner,
            'dailyRank' => $product->dailyRank(),
            'weeklyRank' => $product->weeklyRank(),
            'related' => $related,
        ]);
    }

    /** Called by a beacon when a visitor clicks through to the product's website. */
    public function click(Request $request, Product $product)
    {
        if ($product->isLive() && ! $this->isBot($request)) {
            $seen = $request->session()->get('clicked', []);
            if (! in_array($product->id, $seen)) {
                $product->increment('clicks_count');
                $this->bumpDaily($product, 'clicks');
                $request->session()->push('clicked', $product->id);
            }
        }

        return response()->noContent();
    }

    private function countView(Request $request, Product $product): void
    {
        if ($this->isBot($request)) {
            return;
        }

        $seen = $request->session()->get('viewed', []);
        if (! in_array($product->id, $seen)) {
            $product->increment('views_count');
            $this->bumpDaily($product, 'views');
            $request->session()->push('viewed', $product->id);
        }
    }

    private function isBot(Request $request): bool
    {
        return (bool) preg_match('/bot|crawl|spider|slurp|preview|facebookexternalhit|curl|wget/i', (string) $request->userAgent());
    }

    /** Per-day counters behind the maker dashboard charts. */
    private function bumpDaily(Product $product, string $column): void
    {
        \Illuminate\Support\Facades\DB::table('product_daily_stats')->upsert(
            [['product_id' => $product->id, 'date' => today()->toDateString(), $column => 1]],
            ['product_id', 'date'],
            [$column => \Illuminate\Support\Facades\DB::raw("{$column} + 1")],
        );
    }
}
