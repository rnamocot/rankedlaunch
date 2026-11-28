<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProductUpvoted extends Notification
{
    use Queueable;

    /** Upvote counts that trigger a notification, so makers aren't pinged for every vote. */
    public const MILESTONES = [1, 10, 25, 50, 100, 250, 500, 1000, 2500, 5000];

    public function __construct(public Product $product, public int $votes) {}

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
            'message' => $this->votes === 1
                ? "{$this->product->name} got its first upvote"
                : "{$this->product->name} reached {$this->votes} upvotes",
            'url' => route('product', $this->product),
            'image' => $this->product->logoUrl(),
            'votes' => $this->votes,
        ];
    }
}
