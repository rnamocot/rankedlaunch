<?php

namespace Tests\Feature;

use App\Http\Controllers\BadgeController;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoardAndAwardsTest extends TestCase
{
    use RefreshDatabase;

    private function launch(string $name, $date, int $votes = 0, string $status = 'approved', string $category = 'AI'): Product
    {
        $cat = Category::firstOrCreate(['slug' => str($category)->slug()], ['name' => $category]);

        return Product::create([
            'user_id' => User::factory()->create()->id, 'category_id' => $cat->id, 'name' => $name, 'slug' => str($name)->slug(),
            'url' => 'https://x.test', 'tagline' => "$name tagline", 'description' => str_repeat('x', 50),
            'status' => $status, 'launch_date' => $date, 'votes_count' => $votes,
        ]);
    }

    public function test_feed_shows_today_yesterday_upcoming_and_category_filters(): void
    {
        $this->launch('TodayOne', today(), 3, category: 'Design');
        $this->launch('TodayTwo', today(), 1, category: 'Finance');
        $this->launch('YesterdayOne', today()->subDay(), 9);
        $this->launch('OlderOne', today()->subDays(10), 2);
        $this->launch('TomorrowOne', today()->addDay());
        $this->launch('NotApproved', today()->addDay(), status: 'pending');

        $this->get('/')->assertOk()
            ->assertSeeInOrder(['Launching today', 'TodayOne', 'Yesterday’s top launches', 'YesterdayOne'])
            ->assertSee('TomorrowOne')->assertDontSee('NotApproved')
            ->assertSee(route('home.day', today()->subDays(10)->toDateString()), false)
            ->assertSee('data-filter="design"', false)->assertSee('data-filter="finance"', false);
    }

    public function test_show_more_loads_an_earlier_day_and_points_to_the_next(): void
    {
        $this->launch('OlderOne', today()->subDays(4), 2);
        $this->launch('OldestOne', today()->subDays(9), 1);

        $this->getJson('/days/'.today()->subDays(4)->toDateString())->assertOk()
            ->assertJsonPath('next', today()->subDays(9)->toDateString())
            ->assertSee('OlderOne');

        $this->getJson('/days/'.today()->subDays(9)->toDateString())->assertOk()->assertJsonPath('next', null);
        $this->getJson('/days/'.today()->subDays(5)->toDateString())->assertNotFound();
        $this->getJson('/days/'.today()->toDateString())->assertNotFound();
    }

    public function test_awards_cover_day_week_month_year_top_five_once_period_ends(): void
    {
        // A launch from a past month, ranked #4 that month and #4 that day.
        $date = today()->subMonths(2)->startOfMonth()->addDays(10);
        foreach ([50, 40, 30] as $i => $v) {
            $this->launch("Rival{$i}", $date, $v);
        }
        $mine = $this->launch('Mine', $date, 20);

        $award = $mine->award();
        $this->assertNotNull($award);
        $this->assertContains($award['period'], ['year', 'month']);
        $this->assertSame(4, $award['rank']);
        $this->assertSame(['RANKEDLAUNCH', $award['label']], BadgeController::lines($mine));

        // Today's launch: no award yet (the day isn't over).
        $this->assertNull($this->launch('Fresh', today(), 99)->award());
    }

    public function test_sixth_place_gets_no_award(): void
    {
        $date = today()->subDays(3);
        foreach (range(1, 5) as $i) {
            $this->launch("Top{$i}", $date, 100 - $i);
        }
        $sixth = $this->launch('Sixth', $date, 1);
        $this->assertSame(6, $sixth->dailyRank());
        $this->assertSame(['LAUNCHED ON', 'RankedLaunch'], BadgeController::lines($sixth));
    }

    public function test_how_it_works_page_and_year_rankings(): void
    {
        $this->get('/how-it-works')->assertOk()->assertSee('How launches work')->assertSee('Awards and badges')->assertSee('top 5');
        $this->get('/rankings/year')->assertOk()->assertSee('This year');
    }
}
