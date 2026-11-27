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

    /** Can people buy Pro right now? Locally and in tests checkout is simulated, so always yes. */
    public function available(): bool
    {
        return $this->gateway()->configured() || app()->environment('local', 'testing');
    }
}
