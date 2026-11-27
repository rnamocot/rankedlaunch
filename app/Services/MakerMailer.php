<?php

namespace App\Services;

use App\Mail\LaunchEmail;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/** Sends launch emails to a product's maker. Never breaks the request if mail fails. */
class MakerMailer
{
    public function send(Product $product, string $type): bool
    {
        // X sign-ins without an email get a placeholder address we can't deliver to.
        $email = $product->user?->deliverableEmail();
        if (! $email) {
            return false;
        }

        try {
            Mail::to($email)->send(new LaunchEmail($product, $type));
            \App\Models\ActivityLog::record('email', "Sent “{$type}” email for {$product->name}", $product);

            return true;
        } catch (Throwable $e) {
            Log::error('Launch email failed', ['product' => $product->id, 'type' => $type, 'error' => $e->getMessage()]);
            \App\Models\ActivityLog::record('email', "FAILED “{$type}” email for {$product->name}: ".str($e->getMessage())->limit(120), $product, meta: ['failed' => true]);

            return false;
        }
    }
}
