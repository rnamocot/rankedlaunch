<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class LemonSqueezy
{
    public function name(): string
    {
        return 'lemonsqueezy';
    }

    public function configured(): bool
    {
        $cfg = config('rankedlaunch.lemonsqueezy');

        return filled($cfg['api_key']) && filled($cfg['store_id']);
    }

    /** Create a hosted checkout and return its URL. */
    public function checkoutUrl(Product $product, Payment $payment): string
    {
        $plan = config("rankedlaunch.plans.{$payment->plan}");

        if (empty($plan['variant_id'])) {
            throw new RuntimeException("No LemonSqueezy variant configured for plan [{$payment->plan}].");
        }

        $response = Http::withToken(config('rankedlaunch.lemonsqueezy.api_key'))
            ->accept('application/vnd.api+json')
            ->withBody(json_encode([
                'data' => [
                    'type' => 'checkouts',
                    'attributes' => [
                        // Charge what we quoted (handles the launch-offer price).
                        'custom_price' => $payment->amount,
                        'checkout_data' => [
                            'email' => $product->user->email,
                            'name' => $product->user->name,
                            'custom' => [
                                'payment_id' => (string) $payment->id,
                                'product_id' => (string) $product->id,
                            ],
                        ],
                        'product_options' => [
                            'redirect_url' => route('launch.hub', ['product' => $product, 'paid' => 1]),
                        ],
                    ],
                    'relationships' => [
                        'store' => ['data' => ['type' => 'stores', 'id' => (string) config('rankedlaunch.lemonsqueezy.store_id')]],
                        'variant' => ['data' => ['type' => 'variants', 'id' => (string) $plan['variant_id']]],
                    ],
                ],
            ]), 'application/vnd.api+json')
            ->post('https://api.lemonsqueezy.com/v1/checkouts')
            ->throw();

        return $response->json('data.attributes.url');
    }

    public function validSignature(string $payload, ?string $signature): bool
    {
        $secret = config('rankedlaunch.lemonsqueezy.webhook_secret');

        if (! $secret || ! $signature) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $payload, $secret), $signature);
    }
}
