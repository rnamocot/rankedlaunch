<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Live site with no payment provider: Pro is "coming soon", free listing still works. */
class PaymentsOffTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['env'] = 'production';
        config(['rankedlaunch.polar.access_token' => null, 'rankedlaunch.lemonsqueezy.api_key' => null]);
        // CSRF is only skipped automatically in the testing environment.
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
    }

    public function test_pro_is_coming_soon_and_offer_banners_hide(): void
    {
        $this->get('/pricing')->assertOk()
            ->assertSee('Free while we’re in early access.', false)
            ->assertDontSee('Choose Pro')->assertDontSee('Coming soon')->assertDontSee('dofollow backlink')
            ->assertDontSee('promo-marquee', false);
        $this->get('/launch')->assertOk()->assertSee('Everything is free right now')->assertDontSee('Free or paid?');
        $this->get('/')->assertOk()->assertDontSee('promo-marquee', false);
    }

    public function test_pro_cannot_be_chosen_but_free_listing_works(): void
    {
        $user = User::factory()->create();
        $cat = Category::create(['name' => 'AI', 'slug' => 'ai']);
        $p = Product::create([
            'user_id' => $user->id, 'category_id' => $cat->id, 'name' => 'Acme', 'slug' => 'acme', 'url' => 'https://a.test',
            'tagline' => 't', 'description' => str_repeat('x', 50), 'status' => 'draft',
        ]);

        $this->actingAs($user)->get('/submit/acme/launch')->assertOk()
            ->assertSee('Free while RankedLaunch is in early access')
            ->assertDontSee('value="pro"', false);
        $this->actingAs($user)->post('/submit/acme/launch', ['plan' => 'pro', 'when' => 'now'])->assertSessionHasErrors('plan');
        $this->actingAs($user)->post('/submit/acme/launch', ['plan' => 'free', 'when' => 'now'])->assertSessionHasNoErrors();
        $this->assertSame('free', $p->fresh()->plan);
    }

    public function test_keys_alone_do_not_turn_payments_on_the_switch_is_required(): void
    {
        config(['rankedlaunch.payment_provider' => 'polar', 'rankedlaunch.polar.access_token' => 'live-token', 'rankedlaunch.payments_enabled' => false]);
        $this->get('/pricing')->assertSee('Free while we’re in early access.', false)->assertDontSee('Choose Pro');

        config(['rankedlaunch.payments_enabled' => true]);
        $this->get('/pricing')->assertSee('Choose Pro');
    }
}
