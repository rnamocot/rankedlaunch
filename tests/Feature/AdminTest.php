<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\Vote;
use App\Services\Intelligence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->admin = User::factory()->create();
        $this->admin->forceFill(['is_admin' => true])->save();
    }

    private function product(array $attrs = []): Product
    {
        $category = Category::firstOrCreate(['slug' => 'ai'], ['name' => 'AI']);

        return Product::create($attrs + [
            'user_id' => User::factory()->create()->id, 'category_id' => $category->id, 'name' => 'Acme '.uniqid(),
            'slug' => 'acme-'.uniqid(), 'url' => 'https://acme.test', 'tagline' => 'Does things',
            'description' => str_repeat('x', 50), 'status' => 'approved', 'launch_date' => today(),
        ]);
    }

    public function test_every_admin_page_renders(): void
    {
        $p = $this->product(['votes_count' => 3]);
        Payment::create(['user_id' => $p->user_id, 'product_id' => $p->id, 'provider' => 'polar', 'plan' => 'pro', 'amount' => 2000, 'status' => 'paid']);
        ActivityLog::record('payment', 'Test payment', $p);

        foreach (['/admin', '/admin/products', '/admin/products?status=pending', "/admin/products/{$p->slug}/edit", '/admin/users', "/admin/users/{$p->user_id}",
            '/admin/payments', '/admin/votes', '/admin/subscribers', '/admin/categories', '/admin/activity', '/admin/settings', '/admin/system'] as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk();
        }

        $this->actingAs($this->admin)->get('/admin')->assertSee('Intelligence')->assertSee('Launch flow')->assertSee('Revenue per day');
    }

    public function test_non_admins_get_404(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/settings')->assertNotFound();
    }

    public function test_settings_change_prices_site_wide(): void
    {
        $this->actingAs($this->admin)->put('/admin/settings', [
            'promo__enabled' => '1', 'promo__price' => '15', 'promo__limit' => '10', 'promo__label' => 'Early bird',
            'plans__pro__price' => '35', 'plans__featured__price' => '59', 'plans__featured__featured_days' => '5',
            'free_wait_days' => '21', 'free_slots_per_day' => '8', 'paid_slots_per_day' => '12', 'featured_slots_per_day' => '3',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(1500, config('rankedlaunch.promo.price'));
        $this->assertSame(3500, config('rankedlaunch.plans.pro.price'));
        $this->get('/pricing')->assertSee('$15')->assertSee('$35')->assertSee('$59')->assertSee('Early bird');
        $this->assertDatabaseHas('activity_logs', ['type' => 'settings']);

        app(\App\Services\Settings::class)->apply();
        $this->assertSame(21, config('rankedlaunch.free_wait_days'));
    }

    public function test_admin_can_edit_any_product_field(): void
    {
        $p = $this->product(['status' => 'pending', 'plan' => 'free']);

        $this->actingAs($this->admin)->put("/admin/products/{$p->slug}", [
            'name' => 'Renamed', 'slug' => 'renamed', 'url' => 'https://new.test', 'tagline' => 'New tagline',
            'description' => 'New description long enough.', 'category_id' => $p->category_id, 'pricing_type' => 'paid',
            'plan' => 'featured', 'status' => 'approved', 'launch_date' => today()->addDays(3)->toDateString(),
            'featured_until' => '', 'is_dofollow' => '1', 'rejection_reason' => '',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $p->refresh();
        $this->assertSame(['Renamed', 'renamed', 'featured', 'approved', true], [$p->name, $p->slug, $p->plan, $p->status, $p->is_dofollow]);
    }

    public function test_ban_signs_user_out_and_removes_their_votes(): void
    {
        $p = $this->product();
        $cheater = User::factory()->create();
        Vote::create(['user_id' => $cheater->id, 'product_id' => $p->id]);
        $p->update(['votes_count' => 1]);

        $this->actingAs($this->admin)->post("/admin/users/{$cheater->id}/ban", ['remove_votes' => '1', 'reason' => 'Vote ring'])->assertRedirect();

        $this->assertNotNull($cheater->fresh()->banned_at);
        $this->assertSame(0, $p->fresh()->votes_count);

        $this->actingAs($cheater->fresh())->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_admin_cannot_ban_or_demote_themselves(): void
    {
        $this->actingAs($this->admin)->post("/admin/users/{$this->admin->id}/ban")->assertStatus(422);
        $this->actingAs($this->admin)->post("/admin/users/{$this->admin->id}/admin")->assertStatus(422);
    }

    public function test_refund_marks_payment_and_moves_launch_to_free(): void
    {
        $p = $this->product(['status' => 'pending', 'plan' => 'pro', 'is_dofollow' => true, 'launch_date' => today()->addDays(5)]);
        $pay = Payment::create(['user_id' => $p->user_id, 'product_id' => $p->id, 'provider' => 'polar', 'plan' => 'pro', 'amount' => 2900, 'status' => 'paid']);

        $this->actingAs($this->admin)->post("/admin/payments/{$pay->id}/refund", ['downgrade' => '1'])->assertRedirect();

        $this->assertSame('refunded', $pay->fresh()->status);
        $this->assertSame('free', $p->fresh()->plan);
        $this->assertFalse($p->fresh()->is_dofollow);
    }

    public function test_delete_requires_confirmation(): void
    {
        $p = $this->product();
        $this->actingAs($this->admin)->delete("/admin/products/{$p->slug}")->assertSessionHasErrors('confirm');
        $this->actingAs($this->admin)->delete("/admin/products/{$p->slug}", ['confirm' => '1'])->assertRedirect('/admin/products');
        $this->assertModelMissing($p);
    }

    public function test_intelligence_flags_problems(): void
    {
        $this->product(['status' => 'pending', 'launch_date' => today()->addDay()]);
        $slow = $this->product(['status' => 'pending', 'plan' => 'pro', 'launch_date' => today()->addDays(5)]);
        $slow->timestamps = false;
        $slow->forceFill(['updated_at' => now()->subDay()])->save();

        $titles = collect(app(Intelligence::class)->insights())->pluck('title')->implode(' | ');

        $this->assertStringContainsString('Scheduled emails aren’t running', $titles);
        $this->assertStringContainsString('unreviewed product launch within 2 days', $titles);
        $this->assertStringContainsString('paid launch waiting over 12 hours', $titles);

        $this->assertSame('Sign-ups', app(Intelligence::class)->flow()['stages'][0][0]);
    }

    public function test_categories_crud(): void
    {
        $this->actingAs($this->admin)->post('/admin/categories', ['name' => 'Customer Support'])->assertRedirect();
        $c = Category::where('slug', 'customer-support')->firstOrFail();
        $this->actingAs($this->admin)->put("/admin/categories/{$c->id}", ['name' => 'Support', 'slug' => 'support', 'sort' => 3])->assertRedirect();
        $this->assertSame('support', $c->fresh()->slug);
        $this->actingAs($this->admin)->delete("/admin/categories/{$c->id}")->assertRedirect();
        $this->assertModelMissing($c);
    }
}
