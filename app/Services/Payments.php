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
}
