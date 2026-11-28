<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CommentReplied extends Notification
{
    use Queueable;

    public function __construct(public Comment $reply) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{message: string, url: string, image: ?string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => "{$this->reply->user->name} replied to your comment on {$this->reply->product->name}",
            'excerpt' => str($this->reply->body)->limit(90)->toString(),
            'url' => route('product', $this->reply->product).'#comment-'.$this->reply->id,
            'image' => $this->reply->user->avatar,
        ];
    }
}
