<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LaunchFlowTest extends TestCase
{
    use RefreshDatabase;

    private function submitProduct(User $user): Product
    {
        Storage::fake('public');
        $category = Category::create(['name' => 'Productivity', 'slug' => 'productivity']);

        $this->actingAs($user)->post('/submit', [
            'name' => 'Acme Notes',
            'url' => 'https://acme.test',
            'tagline' => 'Notes that write themselves',
            'description' => str_repeat('A great product for people who take notes. ', 3),
            'category_id' => $category->id,
            'pricing_type' => 'freemium',
            'logo' => UploadedFile::fake()->image('logo.png', 256, 256),
        ])->assertRedirect('/submit/acme-notes/launch');

        return Product::firstWhere('slug', 'acme-notes');
    }

    public function test_free_list_now_goes_on_todays_board_after_review(): void
    {
        $user = User::factory()->create();
        $product = $this->submitProduct($user);

        $this->actingAs($user)->get("/submit/{$product->slug}/launch")->assertOk()->assertSee('List it now')->assertSee('Schedule a launch');
        $this->actingAs($user)->post("/submit/{$product->slug}/launch", ['plan' => 'free', 'when' => 'now'])->assertRedirect();

        $product->refresh();
        $this->assertSame('pending', $product->status);
        $this->assertSame('free', $product->plan);
        $this->assertFalse($product->is_dofollow);
        $this->assertTrue($product->launch_date->isToday());

        // Not public until approved
        $this->app['auth']->forgetGuards();
        $this->get("/apps/{$product->slug}")->assertNotFound();
        $this->actingAs($user)->get("/apps/{$product->slug}")->assertOk();
    }

    public function test_free_launch_can_pick_any_open_day(): void
    {
        $user = User::factory()->create();
        $product = $this->submitProduct($user);
        $day = today()->addDays(2)->toDateString();

        $this->actingAs($user)->post("/submit/{$product->slug}/launch", ['plan' => 'free', 'when' => 'date', 'launch_date' => $day])->assertRedirect();

        $this->assertSame($day, $product->fresh()->launch_date->toDateString());
    }

    public function test_with_review_off_list_now_is_live_instantly(): void
    {
        config(['rankedlaunch.review_listings' => false]);
        $user = User::factory()->create();
        $product = $this->submitProduct($user);

        $this->actingAs($user)->post("/submit/{$product->slug}/launch", ['plan' => 'free', 'when' => 'now'])->assertRedirect();

        $this->assertTrue($product->fresh()->isLive());
        $this->app['auth']->forgetGuards();
        $this->get('/')->assertSee('Acme Notes');
    }

    public function test_paid_launch_flow_with_simulated_payment(): void
    {
        $user = User::factory()->create();
        $product = $this->submitProduct($user);
        $date = today()->addDays(3)->toDateString();

        $response = $this->actingAs($user)->post("/submit/{$product->slug}/launch", ['plan' => 'pro', 'launch_date' => $date]);
        $payment = Payment::first();
        $response->assertRedirect("/checkout/{$payment->id}/simulate");
        $this->assertSame('awaiting_payment', $product->fresh()->status);

        $this->actingAs($user)->get("/checkout/{$payment->id}/simulate")->assertOk();
        $this->actingAs($user)->post("/checkout/{$payment->id}/simulate")->assertRedirect();

        $product->refresh();
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('pro', $product->plan);
        $this->assertSame('pending', $product->status);
        $this->assertTrue($product->is_dofollow);
        $this->assertSame($date, $product->launch_date->toDateString());
    }

    public function test_webhook_requires_valid_signature_and_activates_plan(): void
    {
        config(['rankedlaunch.lemonsqueezy.webhook_secret' => 'secret']);
        $user = User::factory()->create();
        $product = $this->submitProduct($user);
        $product->update(['requested_plan' => 'featured', 'requested_launch_date' => today()->addDays(2), 'status' => 'awaiting_payment']);
        $payment = Payment::create(['user_id' => $user->id, 'product_id' => $product->id, 'provider' => 'lemonsqueezy', 'plan' => 'featured', 'amount' => 9900]);

        $body = json_encode([
            'meta' => ['event_name' => 'order_created', 'custom_data' => ['payment_id' => (string) $payment->id]],
            'data' => ['id' => '555', 'attributes' => ['status' => 'paid', 'total' => 9900, 'currency' => 'USD']],
        ]);

        $this->call('POST', '/webhooks/lemonsqueezy', [], [], [], ['HTTP_X-Signature' => 'bad', 'CONTENT_TYPE' => 'application/json'], $body)->assertStatus(401);

        $sig = hash_hmac('sha256', $body, 'secret');
        $this->call('POST', '/webhooks/lemonsqueezy', [], [], [], ['HTTP_X-Signature' => $sig, 'CONTENT_TYPE' => 'application/json'], $body)->assertOk();

        $product->refresh();
        $this->assertSame('featured', $product->plan);
        $this->assertNotNull($product->featured_until);
        $this->assertSame('555', $payment->fresh()->provider_order_id);
    }

    public function test_admin_approves_and_users_can_vote_once(): void
    {
        $maker = User::factory()->create();
        $product = $this->submitProduct($maker);
        $product->update(['status' => 'pending', 'launch_date' => today()]);

        $this->actingAs($maker)->get('/admin')->assertNotFound();

        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->post("/admin/products/{$product->slug}/approve")->assertRedirect();
        $this->assertTrue($product->fresh()->isLive());

        $this->get('/')->assertSee('Acme Notes');
        $this->get("/apps/{$product->slug}")->assertOk()->assertSee('nofollow');

        $voter = User::factory()->create();
        $this->actingAs($voter)->postJson("/apps/{$product->slug}/vote")->assertJson(['voted' => true, 'count' => 1]);
        $this->actingAs($voter)->postJson("/apps/{$product->slug}/vote")->assertJson(['voted' => false, 'count' => 0]);

        $this->get("/badge/{$product->slug}.svg")->assertOk()->assertHeader('Content-Type', 'image/svg+xml');
    }

    public function test_guests_cannot_vote_or_submit(): void
    {
        $this->postJson('/apps/anything/vote')->assertUnauthorized();
        $this->get('/submit')->assertRedirect('/register');
    }

    public function test_paid_date_must_have_slots(): void
    {
        config(['rankedlaunch.paid_slots_per_day' => 0]);
        $user = User::factory()->create();
        $product = $this->submitProduct($user);

        $this->actingAs($user)
            ->post("/submit/{$product->slug}/launch", ['plan' => 'pro', 'launch_date' => today()->addDays(2)->toDateString()])
            ->assertSessionHasErrors('launch_date');
    }
}
