<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\Polar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Polar emails receipts to the checkout email, so it must be a real address. */
class ReceiptEmailTest extends TestCase
{
    use RefreshDatabase;

    private function setup_(string $email): array
    {
        config([
            'rankedlaunch.polar.access_token' => 'test',
            'rankedlaunch.polar.webhook_secret' => 'whs',
            'rankedlaunch.polar.products' => ['pro' => 'prod_pro', 'pro_promo' => 'prod_promo', 'featured' => 'prod_feat'],
        ]);
        $user = User::factory()->create(['email' => $email]);
        $cat = Category::create(['name' => 'AI', 'slug' => 'ai']);
        $product = Product::create([
            'user_id' => $user->id, 'category_id' => $cat->id, 'name' => 'Acme', 'slug' => 'acme',
            'url' => 'https://acme.test', 'tagline' => 't', 'description' => str_repeat('x', 50),
            'status' => 'awaiting_payment', 'requested_plan' => 'pro', 'requested_launch_date' => today()->addDays(3),
        ]);
        $payment = Payment::create(['user_id' => $user->id, 'product_id' => $product->id, 'provider' => 'polar', 'plan' => 'pro', 'amount' => 2900]);

        return [$user, $product, $payment];
    }

    public function test_real_email_is_prefilled_for_the_receipt(): void
    {
        Http::fake(['*/v1/checkouts/' => Http::response(['url' => 'https://polar.test/checkout'])]);
        [$user, $product, $payment] = $this->setup_('maker@example.com');

        app(Polar::class)->checkoutUrl($product, $payment);

        Http::assertSent(fn ($r) => $r['customer_email'] === 'maker@example.com');
    }

    public function test_placeholder_email_is_not_sent_so_buyer_enters_a_real_one(): void
    {
        Http::fake(['*/v1/checkouts/' => Http::response(['url' => 'https://polar.test/checkout'])]);
        [$user, $product, $payment] = $this->setup_('x_123'.User::PLACEHOLDER_DOMAIN);

        app(Polar::class)->checkoutUrl($product, $payment);

        Http::assertSent(fn ($r) => ! array_key_exists('customer_email', $r->data()));
    }

    public function test_paid_webhook_saves_the_real_checkout_email(): void
    {
        Mail::fake();
        [$user, $product, $payment] = $this->setup_('x_123'.User::PLACEHOLDER_DOMAIN);

        $body = json_encode(['type' => 'order.paid', 'data' => [
            'id' => 'ord_1', 'status' => 'paid', 'total_amount' => 2900, 'currency' => 'usd',
            'customer' => ['email' => 'real@example.com'],
            'metadata' => ['payment_id' => (string) $payment->id],
        ]]);
        $ts = (string) time();
        $sig = 'v1,'.base64_encode(hash_hmac('sha256', "m1.$ts.$body", 'whs', true));

        $this->call('POST', '/webhooks/polar', [], [], [], [
            'HTTP_webhook-id' => 'm1', 'HTTP_webhook-timestamp' => $ts, 'HTTP_webhook-signature' => $sig, 'CONTENT_TYPE' => 'application/json',
        ], $body)->assertStatus(202);

        $this->assertSame('real@example.com', $user->fresh()->email);
        $this->assertSame('real@example.com', $user->fresh()->deliverableEmail());
        Mail::assertSent(\App\Mail\LaunchEmail::class, fn ($m) => $m->hasTo('real@example.com'));
    }
}
