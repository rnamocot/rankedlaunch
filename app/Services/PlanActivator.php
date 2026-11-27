<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Product;

class PlanActivator
{
    /** Apply a paid plan to a product once the payment is confirmed. */
    public function activate(Product $product, Payment $payment): void
    {
        $plan = $payment->plan;

        $product->plan = $plan;
        $product->is_dofollow = true;
        $product->launch_date = $product->requested_launch_date ?? today();
        $product->requested_plan = null;

        if ($product->status === 'awaiting_payment' || $product->status === 'draft') {
            $product->status = config('rankedlaunch.review_listings') ? 'pending' : 'approved';
        }

        if ($plan === 'pro') {
            $days = config('rankedlaunch.plans.pro.featured_days', 7);
            $product->featured_until = $product->launch_date->copy()->startOfDay()->addDays($days);
        }

        $product->save();

        \App\Models\ActivityLog::record('payment', "{$product->name} paid ".Pricing::dollars($payment->amount)." for {$plan} ({$payment->provider})", $product, meta: ['payment_id' => $payment->id]);

        app(MakerMailer::class)->send($product->loadMissing('user'), 'booked');
    }
}
