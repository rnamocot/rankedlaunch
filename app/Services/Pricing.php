<?php

namespace App\Services;

use App\Models\Payment;

/**
 * Plan prices, including the limited launch offer
 * (e.g. first 50 Listings at $20, then back to the normal price).
 */
class Pricing
{
    /** Unpaid checkouts hold a promo spot for this long so we don't oversell. */
    private const HOLD_MINUTES = 30;

    public function promo(): array
    {
        return config('rankedlaunch.promo');
    }

    public function promoActive(): bool
    {
        $promo = $this->promo();

        return $promo['enabled'] && $this->promoRemaining() > 0;
    }

    public function promoRemaining(): int
    {
        return max(0, $this->promo()['limit'] - $this->promoClaimed());
    }

    public function promoClaimed(): int
    {
        // Cached on the current request so every view shares one query.
        $attributes = request()->attributes;
        if ($attributes->has('promo_claimed')) {
            return $attributes->get('promo_claimed');
        }

        $count = Payment::where('plan', $this->promo()['plan'])
            ->where('is_promo', true)
            ->where(fn ($q) => $q->where('status', 'paid')
                ->orWhere(fn ($q) => $q->where('status', 'pending')->where('created_at', '>=', now()->subMinutes(self::HOLD_MINUTES))))
            ->count();

        $attributes->set('promo_claimed', $count);

        return $count;
    }

    public function isPromoPlan(string $plan): bool
    {
        return $plan === $this->promo()['plan'] && $this->promoActive();
    }

    /** Price in cents a buyer pays right now. */
    public function priceFor(string $plan): int
    {
        return $this->isPromoPlan($plan)
            ? $this->promo()['price']
            : config("rankedlaunch.plans.$plan.price");
    }

    public function regularPrice(string $plan): int
    {
        return config("rankedlaunch.plans.$plan.price");
    }

    public static function dollars(int $cents): string
    {
        return '$'.rtrim(rtrim(number_format($cents / 100, 2), '0'), '.');
    }
}
