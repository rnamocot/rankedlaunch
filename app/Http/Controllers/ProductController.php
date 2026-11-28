<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\CommentVote;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        // Comments: most upvoted first by default, or newest.
        $commentSort = $request->query('comments') === 'new' ? 'new' : 'top';
        $comments = $product->isLive()
            ? Comment::with('user', 'replies.user')
                ->where('product_id', $product->id)->whereNull('parent_id')
                ->when($commentSort === 'top', fn ($q) => $q->orderByDesc('votes_count'))
                ->latest()
                ->get()
            : collect();

        $commentVotedIds = $user && $comments->isNotEmpty()
            ? CommentVote::where('user_id', $user->id)
                ->whereIn('comment_id', $comments->pluck('id')->merge($comments->flatMap->replies->pluck('id')))
                ->pluck('comment_id')->flip()->all()
            : [];

        $maker = $product->user;
        $makerStats = $maker ? [
            'launches' => $maker->products()->live()->count(),
            'upvotes' => (int) $maker->products()->live()->sum('votes_count'),
        ] : null;

        $launch = $product->launch_date;

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
            'monthlyRank' => $launch ? $product->rankBetween($launch->copy()->startOfMonth(), $launch->copy()->endOfMonth()) : null,
            'award' => $product->award(),
            'isSaved' => $user && $product->bookmarks()->where('user_id', $user->id)->exists(),
            'related' => $related,
            'comments' => $comments,
            'commentSort' => $commentSort,
            'commentVotedIds' => $commentVotedIds,
            'makerStats' => $makerStats,
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
        DB::table('product_daily_stats')->upsert(
            [['product_id' => $product->id, 'date' => today()->toDateString(), $column => 1]],
            ['product_id', 'date'],
            [$column => DB::raw("{$column} + 1")],
        );
    }
}
