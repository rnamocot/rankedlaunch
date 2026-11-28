<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Comment;
use App\Models\CommentVote;
use App\Models\LaunchReminder;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Signed-in toggles: save a product, "notify me" on an upcoming launch, upvote a comment. */
class EngagementController extends Controller
{
    public function bookmark(Request $request, Product $product): JsonResponse
    {
        abort_unless($product->status === 'approved' && $product->launch_date, 404);

        $existing = Bookmark::where('user_id', $request->user()->id)->where('product_id', $product->id)->first();
        $existing ? $existing->delete() : Bookmark::create(['user_id' => $request->user()->id, 'product_id' => $product->id]);

        return response()->json(['active' => ! $existing]);
    }

    public function remind(Request $request, Product $product): JsonResponse
    {
        abort_unless($product->status === 'approved' && $product->launch_date?->isFuture(), 404);

        $existing = LaunchReminder::where('user_id', $request->user()->id)->where('product_id', $product->id)->first();
        $existing ? $existing->delete() : LaunchReminder::create(['user_id' => $request->user()->id, 'product_id' => $product->id]);

        return response()->json(['active' => ! $existing]);
    }

    public function voteComment(Request $request, Comment $comment): JsonResponse
    {
        $user = $request->user();
        abort_if($comment->user_id === $user->id, 422, 'You can’t upvote your own comment.');

        $voted = DB::transaction(function () use ($user, $comment) {
            $existing = CommentVote::where('user_id', $user->id)->where('comment_id', $comment->id)->lockForUpdate()->first();

            if ($existing) {
                $existing->delete();
                $comment->decrement('votes_count');

                return false;
            }

            CommentVote::create(['user_id' => $user->id, 'comment_id' => $comment->id]);
            $comment->increment('votes_count');

            return true;
        });

        return response()->json(['active' => $voted, 'count' => $comment->fresh()->votes_count]);
    }
}
