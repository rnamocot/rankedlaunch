<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MakerExperienceTest extends TestCase
{
    use RefreshDatabase;

    private function product(User $user, array $attrs = []): Product
    {
        $category = Category::firstOrCreate(['slug' => 'ai'], ['name' => 'AI']);

        return Product::create($attrs + [
            'user_id' => $user->id, 'category_id' => $category->id, 'name' => 'Acme',
            'slug' => 'acme', 'url' => 'https://acme.test', 'tagline' => 'Does things',
            'description' => str_repeat('x', 50), 'status' => 'draft',
        ]);
    }

    public function test_guest_clicking_submit_lands_on_signup(): void
    {
        $this->get('/submit')->assertRedirect('/register');
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_free_launch_goes_to_launch_hub_with_next_steps(): void
    {
        $user = User::factory()->create();
        $product = $this->product($user);

        $this->actingAs($user)->post('/submit/acme/launch', ['plan' => 'free'])->assertRedirect('/dashboard/acme');

        $this->actingAs($user)->get('/dashboard/acme')->assertOk()
            ->assertSee('What happens next')
            ->assertSee('Your launch starts in')
            ->assertSee('Make this launch help your SEO')
            ->assertSee('Share kit')
            ->assertSee('Launch-day checklist');

        $other = User::factory()->create();
        $this->actingAs($other)->get('/dashboard/acme')->assertForbidden();
    }

    public function test_live_hub_shows_stats_and_views_and_clicks_are_counted_once(): void
    {
        $maker = User::factory()->create();
        $this->product($maker, ['status' => 'approved', 'launch_date' => today(), 'plan' => 'pro']);

        $this->get('/apps/acme')->assertOk();
        $this->get('/apps/acme')->assertOk(); // same session, not double counted
        $this->post('/apps/acme/click')->assertNoContent();
        $this->post('/apps/acme/click')->assertNoContent();

        $product = Product::first();
        $this->assertSame(1, $product->views_count);
        $this->assertSame(1, $product->clicks_count);

        // Bots don't count
        $this->withHeader('User-Agent', 'Googlebot/2.1')->get('/apps/acme');
        $this->assertSame(1, $product->fresh()->views_count);

        $this->actingAs($maker)->get('/dashboard/acme')->assertOk()->assertSee('Live now')->assertSee('Visits to your site');
    }

    public function test_launch_page_renders(): void
    {
        $this->get('/launch')->assertOk()->assertSee('What a launch here gets you')->assertSee('Free or paid?');
    }

    public function test_polar_webhook_verifies_signature_and_activates_plan(): void
    {
        config(['rankedlaunch.polar.webhook_secret' => 'polar_whs_test']);
        $user = User::factory()->create();
        $product = $this->product($user, ['status' => 'awaiting_payment', 'requested_plan' => 'pro', 'requested_launch_date' => today()->addDays(3)]);
        $payment = Payment::create(['user_id' => $user->id, 'product_id' => $product->id, 'provider' => 'polar', 'plan' => 'pro', 'amount' => 2500, 'is_promo' => true]);

        $body = json_encode(['type' => 'order.paid', 'data' => [
            'id' => 'ord_123', 'status' => 'paid', 'total_amount' => 2500, 'currency' => 'usd',
            'metadata' => ['payment_id' => (string) $payment->id],
        ]]);
        $id = 'msg_1';
        $ts = (string) time();
        $sig = 'v1,'.base64_encode(hash_hmac('sha256', "$id.$ts.$body", 'polar_whs_test', true));

        $send = fn ($signature) => $this->call('POST', '/webhooks/polar', [], [], [], [
            'HTTP_webhook-id' => $id, 'HTTP_webhook-timestamp' => $ts, 'HTTP_webhook-signature' => $signature, 'CONTENT_TYPE' => 'application/json',
        ], $body);

        $send('v1,bad')->assertStatus(401);
        $send($sig)->assertStatus(202);

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('ord_123', $payment->fresh()->provider_order_id);
        $this->assertSame('pro', $product->fresh()->plan);
        $this->assertTrue($product->fresh()->is_dofollow);
    }

    public function test_polar_uses_promo_product_for_launch_offer(): void
    {
        config(['rankedlaunch.polar.products' => ['pro' => 'prod_pro', 'pro_promo' => 'prod_pro_25']]);
        $polar = app(\App\Services\Polar::class);

        $this->assertSame('prod_pro_25', $polar->productId(new Payment(['plan' => 'pro', 'is_promo' => true])));
        $this->assertSame('prod_pro', $polar->productId(new Payment(['plan' => 'pro', 'is_promo' => false])));
    }
}
