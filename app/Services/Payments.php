<?php

namespace App\Services;

/** Picks the active payment provider from PAYMENT_PROVIDER (polar or lemonsqueezy). */
class Payments
{
    public function __construct(private Polar $polar, private LemonSqueezy $lemon) {}

    public function gateway(): Polar|LemonSqueezy
    {
        return config('rankedlaunch.payment_provider') === 'lemonsqueezy' ? $this->lemon : $this->polar;
    }

    /**
     * Can people buy Pro right now? Needs the admin switch on AND the provider's keys.
     * Locally and in tests checkout is simulated, so always yes there.
     */
    public function available(): bool
    {
        if (app()->environment('local', 'testing')) {
            return true;
        }

        return (bool) config('rankedlaunch.payments_enabled') && $this->gateway()->configured();
    }
}
