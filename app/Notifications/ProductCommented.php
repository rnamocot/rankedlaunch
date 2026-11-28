<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProductCommented extends Notification
{
    use Queueable;

    public function __construct(public Comment $comment) {}

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
            'message' => "{$this->comment->user->name} commented on {$this->comment->product->name}",
            'excerpt' => str($this->comment->body)->limit(90)->toString(),
            'url' => route('product', $this->comment->product).'#comment-'.$this->comment->id,
            'image' => $this->comment->user->avatar,
        ];
    }
}
