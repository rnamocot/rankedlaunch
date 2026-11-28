<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShowHnImportTest extends TestCase
{
    use RefreshDatabase;

    private function fakeHn(): void
    {
        Storage::fake('public');
        $items = [
            1 => ['title' => 'Show HN: Olumino – A Markdown notes and spaced repetition app', 'url' => 'https://olumino.app', 'score' => 80, 'by' => 'maker1'],
            2 => ['title' => 'Show HN: I restored an old phone OS', 'url' => 'https://example.org/thing', 'score' => 300],
            3 => ['title' => 'Show HN: Tiny – a blog engine', 'url' => 'https://tiny.dev/blog/2026/11/launch', 'score' => 90],
            4 => ['title' => 'Show HN: Lowscore – barely noticed', 'url' => 'https://lowscore.dev', 'score' => 3],
            5 => ['title' => 'Show HN: SimDrop', 'url' => 'https://github.com/someone/sim-drop', 'score' => 50, 'text' => 'Run iOS simulators from the Finder sidebar. More text here.'],
            6 => ['title' => 'Ask HN: Not a show', 'url' => 'https://ask.dev', 'score' => 99],
        ];

        Http::fake([
            'hacker-news.firebaseio.com/v0/showstories.json' => Http::response(array_keys($items)),
            'hacker-news.firebaseio.com/v0/item/*' => function ($request) use ($items) {
                $id = (int) basename(parse_url($request->url(), PHP_URL_PATH), '.json');

                return Http::response(['id' => $id, 'type' => 'story'] + $items[$id]);
            },
        ]);
    }

    public function test_imports_products_and_skips_stories_blogs_low_scores_and_non_show_posts(): void
    {
        Category::create(['name' => 'Notes & Docs', 'slug' => 'notes-docs']);
        $this->fakeHn();

        $this->artisan('listings:import-show-hn', ['--min-score' => 20])->assertSuccessful();

        $this->assertSame(['Olumino', 'SimDrop'], Product::orderBy('id')->pluck('name')->all());

        $olumino = Product::where('name', 'Olumino')->first();
        $this->assertSame('A Markdown notes and spaced repetition app', $olumino->tagline);
        $this->assertSame('notes-docs', $olumino->category->slug);
        $this->assertSame('https://news.ycombinator.com/item?id=1', $olumino->source_url);
        $this->assertSame('rankedlaunch', $olumino->user->username);
        $this->assertSame('Run iOS simulators from the Finder sidebar', Product::where('name', 'SimDrop')->value('tagline'));

        // Running again adds nothing new.
        $this->artisan('listings:import-show-hn', ['--min-score' => 20])->assertSuccessful();
        $this->assertSame(2, Product::count());
    }

    public function test_listings_show_in_discover_but_never_rank_or_win_awards(): void
    {
        $this->fakeHn();
        $this->artisan('listings:import-show-hn', ['--min-score' => 20]);
        $maker = User::factory()->create();
        Product::create([
            'user_id' => $maker->id, 'name' => 'Real Launch', 'slug' => 'real-launch', 'url' => 'https://real.test',
            'tagline' => 'A real launch', 'description' => str_repeat('x', 50), 'status' => 'approved', 'launch_date' => today(), 'votes_count' => 1,
        ]);
        Product::where('name', 'Olumino')->update(['votes_count' => 50, 'launch_date' => today()->subDays(3)]);
        $listing = Product::where('name', 'Olumino')->first();

        $this->get('/discover')->assertOk()->assertSee('Olumino')->assertSee('Via Show HN');
        $this->get('/rankings/all-time')->assertOk()->assertSee('Real Launch')->assertDontSee('Olumino');
        $this->assertNull($listing->dailyRank());
        $this->assertNull($listing->award());
        $this->assertSame(1, Product::where('name', 'Real Launch')->first()->dailyRank());

        $this->get('/apps/'.$listing->slug)->assertOk()->assertSee('Directory listing')->assertSee('Is this yours? Claim it')->assertDontSee('Launched by');
        $this->get('/makers/all-time')->assertOk()->assertDontSee(route('profile', 'rankedlaunch'), false);
    }

    public function test_hourly_job_runs_the_import_once_per_day_when_enabled(): void
    {
        config(['rankedlaunch.show_hn_import.enabled' => true, 'rankedlaunch.show_hn_import.min_score' => 20]);
        $this->fakeHn();

        $this->artisan('launches:send-emails')->assertSuccessful();
        $this->artisan('launches:send-emails')->assertSuccessful();

        $this->assertSame(2, Product::count());
        $this->assertSame(1, Http::recorded(fn ($request) => str_contains($request->url(), 'showstories.json'))->count());
    }
}
