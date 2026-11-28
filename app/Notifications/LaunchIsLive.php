<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LaunchIsLive extends Notification
{
    use Queueable;

    public function __construct(public Product $product) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable->deliverableEmail() ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->product->name} is live on RankedLaunch")
            ->greeting('It’s launch day!')
            ->line("You asked us to tell you when {$this->product->name} goes live. It’s on today’s board now: {$this->product->tagline}")
            ->action('See the launch', route('product', $this->product))
            ->line('Voting is open for 24 hours.');
    }

    /**
     * @return array{message: string, url: string, image: ?string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => "{$this->product->name} is live. You asked to be notified",
            'url' => route('product', $this->product),
            'image' => $this->product->logoUrl(),
        ];
    }
}
