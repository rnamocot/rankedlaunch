<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\LaunchScheduler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FeaturedFairnessTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $attrs = []): Product
    {
        $cat = Category::firstOrCreate(['slug' => 'ai'], ['name' => 'AI']);

        return Product::create($attrs + [
            'user_id' => User::factory()->create()->id, 'category_id' => $cat->id,
            'name' => 'P'.uniqid(), 'slug' => 'p-'.uniqid(), 'url' => 'https://x.test', 'tagline' => 't',
            'description' => str_repeat('x', 50), 'status' => 'approved', 'launch_date' => today(), 'plan' => 'free',
        ]);
    }

    public function test_pro_has_its_own_daily_cap_inside_the_paid_cap(): void
    {
        config(['rankedlaunch.featured_slots_per_day' => 2, 'rankedlaunch.paid_slots_per_day' => 10]);
        $date = today()->addDays(3);
        $this->product(['plan' => 'featured', 'launch_date' => $date]);
        $this->product(['plan' => 'featured', 'launch_date' => $date]);

        $scheduler = app(LaunchScheduler::class);
        $this->assertFalse($scheduler->available($date, 'featured'));
        $this->assertTrue($scheduler->available($date, 'pro'));

        $pick = fn ($plan) => collect($scheduler->dateOptions($plan))->first(fn ($d) => $d['date']->isSameDay($date))['left'];
        $this->assertSame([8, 0], [$pick('pro'), $pick('featured')]);
    }

    public function test_booking_pro_on_a_full_pro_day_is_refused(): void
    {
        Mail::fake();
        config(['rankedlaunch.featured_slots_per_day' => 1]);
        $date = today()->addDays(4);
        $this->product(['plan' => 'featured', 'launch_date' => $date]);

        $mine = $this->product(['status' => 'draft', 'launch_date' => null]);
        $this->actingAs($mine->user)
            ->post("/submit/{$mine->slug}/launch", ['plan' => 'featured', 'launch_date' => $date->toDateString()])
            ->assertSessionHasErrors('launch_date');

        // Listing on the same day still works
        $this->actingAs($mine->user)
            ->post("/submit/{$mine->slug}/launch", ['plan' => 'pro', 'launch_date' => $date->toDateString()])
            ->assertSessionHasNoErrors();
    }

    public function test_rotation_puts_launch_day_first_and_shares_views_evenly(): void
    {
        $launchDay = $this->product(['plan' => 'featured', 'launch_date' => today(), 'featured_until' => now()->addDays(7)]);
        $others = collect(range(1, 5))->map(fn ($i) => $this->product([
            'plan' => 'featured', 'launch_date' => today()->subDays($i), 'featured_until' => now()->addDays(7 - $i),
        ]));

        $shown = [];
        $start = today()->setTime(9, 0);
        // Sample every 10 minutes across 5 hours
        for ($slot = 0; $slot < 30; $slot++) {
            $row = Product::featuredRotation(3, $start->copy()->addMinutes($slot * 10));
            $this->assertCount(3, $row);
            $this->assertTrue($row->first()->is($launchDay), 'Launch-day Pro app must always lead');
            foreach ($row->skip(1) as $p) {
                $shown[$p->id] = ($shown[$p->id] ?? 0) + 1;
            }
        }

        // 30 slots × 2 rotating spots = 60 views split across 5 apps: 12 each.
        $this->assertCount(5, $shown);
        $this->assertSame([12], array_values(array_unique($shown)));
    }

    public function test_paying_never_changes_rank_and_pro_gets_a_label(): void
    {
        $free = $this->product(['plan' => 'free', 'votes_count' => 50, 'name' => 'FreeApp']);
        $pro = $this->product(['plan' => 'featured', 'votes_count' => 10, 'name' => 'ProApp', 'featured_until' => now()->addDays(7)]);

        $this->assertSame(1, $free->dailyRank());
        $this->assertSame(2, $pro->dailyRank());

        $html = $this->get('/rankings/today')->assertOk()->getContent();
        $this->assertLessThan(strpos($html, 'ProApp'), strpos($html, 'FreeApp'));
        $this->assertStringContainsString('Featured', $html);
    }
}
