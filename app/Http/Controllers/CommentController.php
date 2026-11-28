<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Comment;
use App\Models\Product;
use App\Notifications\CommentReplied;
use App\Notifications\ProductCommented;
use App\Services\ChatModerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/** Comments on live product pages. Signed-in users only; same filters as the chat. */
class CommentController extends Controller
{
    public function store(Request $request, Product $product, ChatModerator $moderator)
    {
        abort_unless($product->isLive(), 404);

        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:1000'],
            'parent_id' => ['nullable', 'integer'],
        ]);
        $body = trim(preg_replace("/[ \t]+/", ' ', $data['body']));

        // Replies are one level deep and must belong to this product.
        $parentId = null;
        if (! empty($data['parent_id'])) {
            $parent = Comment::where('product_id', $product->id)->findOrFail($data['parent_id']);
            $parentId = $parent->parent_id ?? $parent->id;
        }

        $user = $request->user();
        $slowKey = "comment:slow:{$user->id}";
        if (Cache::has($slowKey)) {
            return back()->withInput()->withErrors(['body' => 'Please wait a few seconds before commenting again.'])->withFragment('comments');
        }

        if ($reason = $moderator->check($body) ?? $moderator->spamReason($body)) {
            return back()->withInput()->withErrors(['body' => $reason])->withFragment('comments');
        }

        Cache::put($slowKey, true, now()->addSeconds(15));

        $comment = Comment::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'parent_id' => $parentId,
            'body' => $body,
        ]);

        $this->notifyAbout($comment, $parent ?? null);

        return redirect()->to(route('product', $product).'#comment-'.$comment->id)->with('status', 'Comment posted.');
    }

    /** The parent comment's author gets "replied"; the maker gets "commented" (once, never for their own words). */
    private function notifyAbout(Comment $comment, ?Comment $parent): void
    {
        $comment->load('user', 'product.user');
        $maker = $comment->product->user;
        $notified = [$comment->user_id];

        if ($parent && $parent->user && ! in_array($parent->user_id, $notified, true)) {
            $parent->user->notify(new CommentReplied($comment));
            $notified[] = $parent->user_id;
        }

        if ($maker && ! in_array($maker->id, $notified, true)) {
            $maker->notify(new ProductCommented($comment));
        }
    }

    public function destroy(Request $request, Comment $comment)
    {
        $user = $request->user();
        abort_unless($user->id === $comment->user_id || $user->is_admin, 403);

        if ($user->is_admin && $user->id !== $comment->user_id) {
            ActivityLog::record('moderation', "Deleted a comment by {$comment->user?->name} on {$comment->product?->name}", $comment->product, $user);
        }

        $product = $comment->product;
        $comment->delete();

        return redirect()->to(route('product', $product).'#comments')->with('status', 'Comment deleted.');
    }
}
