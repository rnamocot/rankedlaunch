<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Vote;
use App\Notifications\ProductUpvoted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoteController extends Controller
{
    public function toggle(Request $request, Product $product)
    {
        abort_unless($product->isLive(), 404);

        $user = $request->user();

        $voted = DB::transaction(function () use ($user, $product) {
            $existing = Vote::where('user_id', $user->id)->where('product_id', $product->id)->lockForUpdate()->first();

            if ($existing) {
                $existing->delete();
                $product->decrement('votes_count');

                return false;
            }

            Vote::create(['user_id' => $user->id, 'product_id' => $product->id]);
            $product->increment('votes_count');

            return true;
        });

        $count = $product->fresh()->votes_count;

        // Tell the maker at milestones (1st, 10th, 25th…), never about their own vote.
        if ($voted && $product->user && $product->user_id !== $user->id && in_array($count, ProductUpvoted::MILESTONES, true)) {
            $alreadySent = $product->user->notifications()
                ->where('type', ProductUpvoted::class)
                ->where('data->url', route('product', $product))
                ->where('data->votes', $count)
                ->exists();

            if (! $alreadySent) {
                $product->user->notify(new ProductUpvoted($product, $count));
            }
        }

        return response()->json([
            'voted' => $voted,
            'count' => $count,
        ]);
    }
}
