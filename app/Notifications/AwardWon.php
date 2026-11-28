<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AwardWon extends Notification
{
    use Queueable;

    public function __construct(public Product $product, public string $label) {}

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
            'message' => "{$this->product->name} won {$this->label}",
            'url' => route('launch.hub', $this->product),
            'image' => $this->product->logoUrl(),
        ];
    }
}
