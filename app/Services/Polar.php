<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Polar (polar.sh) checkout + webhook verification.
 * Webhooks follow the Standard Webhooks spec (webhook-id / -timestamp / -signature headers).
 */
class Polar
{
    public function name(): string
    {
        return 'polar';
    }

    public function configured(): bool
    {
        return filled(config('rankedlaunch.polar.access_token'));
    }

    private function baseUrl(): string
    {
        return config('rankedlaunch.polar.sandbox') ? 'https://sandbox-api.polar.sh' : 'https://api.polar.sh';
    }

    /** Polar product ID for a plan. The launch offer uses its own product (e.g. Listing at $20). */
    public function productId(Payment $payment): string
    {
        $products = config('rankedlaunch.polar.products');
        $key = $payment->is_promo ? $payment->plan.'_promo' : $payment->plan;
        $id = $products[$key] ?? null;

        if (! $id) {
            throw new RuntimeException("No Polar product configured for [{$key}].");
        }

        return $id;
    }

    public function checkoutUrl(Product $product, Payment $payment): string
    {
        $response = Http::withToken(config('rankedlaunch.polar.access_token'))
            ->acceptJson()
            ->post($this->baseUrl().'/v1/checkouts/', [
                'products' => [$this->productId($payment)],
                'success_url' => route('launch.hub', ['product' => $product, 'paid' => 1]),
                'customer_email' => $product->user->email,
                'customer_name' => $product->user->name,
                'metadata' => [
                    'payment_id' => (string) $payment->id,
                    'product_id' => (string) $product->id,
                ],
            ])
            ->throw();

        return $response->json('url');
    }

    public function validSignature(string $payload, ?string $id, ?string $timestamp, ?string $signatures): bool
    {
        $secret = config('rankedlaunch.polar.webhook_secret');

        if (! $secret || ! $id || ! $timestamp || ! $signatures) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > 300) {
            return false; // replay protection
        }

        $key = str_starts_with($secret, 'whsec_') ? base64_decode(substr($secret, 6)) : $secret;
        $expected = base64_encode(hash_hmac('sha256', "{$id}.{$timestamp}.{$payload}", $key, true));

        foreach (explode(' ', $signatures) as $signature) {
            [$version, $value] = array_pad(explode(',', $signature, 2), 2, null);
            if ($version === 'v1' && $value && hash_equals($expected, $value)) {
                return true;
            }
        }

        return false;
    }
}
