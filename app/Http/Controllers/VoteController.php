<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Vote;
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

        return response()->json([
            'voted' => $voted,
            'count' => $product->fresh()->votes_count,
        ]);
    }
}
