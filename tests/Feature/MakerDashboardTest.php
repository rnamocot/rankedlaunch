<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MakerDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function product(User $user, array $attrs = []): Product
    {
        $cat = Category::firstOrCreate(['slug' => 'marketing'], ['name' => 'Marketing']);

        return Product::create($attrs + [
            'user_id' => $user->id, 'category_id' => $cat->id, 'name' => 'P'.uniqid(), 'slug' => 'p-'.uniqid(),
            'url' => 'https://x.test', 'tagline' => 'Tagline here', 'description' => str_repeat('x', 50),
            'status' => 'approved', 'plan' => 'pro', 'launch_date' => today()->addDays(5),
        ]);
    }

    /* ---------- Rescheduling ---------- */

    public function test_paid_launch_can_move_to_another_open_date(): void
    {
        $user = User::factory()->create();
        $p = $this->product($user);
        $new = today()->addDays(2)->toDateString();

        $this->actingAs($user)->get("/dashboard/{$p->slug}/date")->assertOk()->assertSee('Change launch date');
        $this->actingAs($user)->post("/dashboard/{$p->slug}/date", ['launch_date' => $new])->assertRedirect("/dashboard/{$p->slug}");

        $this->assertSame($new, $p->fresh()->launch_date->toDateString());
    }

    public function test_free_launch_can_move_to_any_open_day_including_today(): void
    {
        $user = User::factory()->create();
        $p = $this->product($user, ['plan' => 'free', 'launch_date' => today()->addDays(30)]);

        $sooner = today()->addDays(2)->toDateString();
        $this->actingAs($user)->post("/dashboard/{$p->slug}/date", ['launch_date' => $sooner])->assertSessionHasNoErrors();
        $this->assertSame($sooner, $p->fresh()->launch_date->toDateString());

        // ...but not onto a full day
        config(['rankedlaunch.free_slots_per_day' => 1]);
        $full = today()->addDays(5);
        $this->product(User::factory()->create(), ['plan' => 'free', 'launch_date' => $full]);
        $this->actingAs($user)->post("/dashboard/{$p->slug}/date", ['launch_date' => $full->toDateString()])->assertSessionHasErrors('launch_date');
    }

    public function test_live_launches_and_other_peoples_launches_cannot_be_moved(): void
    {
        $user = User::factory()->create();
        $live = $this->product($user, ['launch_date' => today()]);
        $this->actingAs($user)->get("/dashboard/{$live->slug}/date")->assertForbidden();

        $theirs = $this->product(User::factory()->create());
        $this->actingAs($user)->get("/dashboard/{$theirs->slug}/date")->assertForbidden();
    }

    public function test_pro_moves_follow_the_pro_daily_cap_and_feature_window(): void
    {
        config(['rankedlaunch.paid_slots_per_day' => 1]);
        $user = User::factory()->create();
        $full = today()->addDays(3);
        $this->product(User::factory()->create(), ['plan' => 'pro', 'launch_date' => $full]);
        $mine = $this->product($user, ['plan' => 'pro', 'launch_date' => today()->addDays(6), 'featured_until' => today()->addDays(13)]);

        $this->actingAs($user)->post("/dashboard/{$mine->slug}/date", ['launch_date' => $full->toDateString()])->assertSessionHasErrors('launch_date');

        $this->actingAs($user)->post("/dashboard/{$mine->slug}/date", ['launch_date' => today()->addDays(4)->toDateString()])->assertRedirect();
        $this->assertSame(today()->addDays(11)->toDateString(), $mine->fresh()->featured_until->toDateString());
    }

    /* ---------- Dashboard ---------- */

    public function test_dashboard_groups_launches_and_shows_totals(): void
    {
        $user = User::factory()->create(['name' => 'Renie Namocot']);
        $this->product($user, ['name' => 'LiveOne', 'launch_date' => today(), 'votes_count' => 7, 'views_count' => 40]);
        $this->product($user, ['name' => 'SoonOne']);
        $this->product($user, ['name' => 'DraftOne', 'status' => 'draft', 'launch_date' => null]);

        $this->actingAs($user)->get('/dashboard')->assertOk()
            ->assertSee('Hi, Renie')
            ->assertSeeInOrder(['Live today', 'LiveOne', 'Needs attention', 'DraftOne', 'Upcoming', 'SoonOne'])
            ->assertSee('Best daily rank')
            ->assertSee('Your upvotes');
    }

    /* ---------- Account ---------- */

    public function test_account_profile_and_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password-1']);

        $this->actingAs($user)->get('/account')->assertOk();
        $this->actingAs($user)->put('/account', ['name' => 'New Name', 'email' => 'new@example.com'])->assertSessionHasNoErrors();
        $this->assertSame('new@example.com', $user->fresh()->email);

        $this->actingAs($user)->put('/account/password', ['current_password' => 'wrong', 'password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass'])
            ->assertSessionHasErrors('current_password');
        $this->actingAs($user)->put('/account/password', ['current_password' => 'old-password-1', 'password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('brand-new-pass', $user->fresh()->password));
    }

    public function test_social_user_can_set_a_first_password(): void
    {
        $user = User::factory()->create(['password' => null, 'provider' => 'google']);

        $this->actingAs($user)->put('/account/password', ['password' => 'first-pass-123', 'password_confirmation' => 'first-pass-123'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('first-pass-123', $user->fresh()->password));
    }

    /* ---------- Live banner ---------- */

    public function test_banner_shows_categories_and_latest_upvotes(): void
    {
        $maker = User::factory()->create();
        $a = $this->product($maker, ['name' => 'Alpha', 'launch_date' => today()]);
        $this->product($maker, ['name' => 'Beta', 'launch_date' => today()]);
        $this->product($maker, ['name' => 'Gamma', 'launch_date' => today()]);
        Vote::create(['user_id' => User::factory()->create()->id, 'product_id' => $a->id]);

        $this->get('/')->assertOk()->assertSee('data-ticker', false)->assertSee('Marketing');

        $this->getJson('/live/today.json')->assertOk()
            ->assertJsonPath('top.0.category', 'Marketing')
            ->assertJsonPath('latest.0.name', 'Alpha')
            ->assertJsonPath('categories.0.name', 'Marketing')
            ->assertJsonPath('categories.0.count', 3)
            ->assertJsonMissingPath('latest.0.user');
    }
}
