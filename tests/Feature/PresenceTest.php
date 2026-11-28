<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vote;
use App\Services\VisitorTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PresenceTest extends TestCase
{
    use RefreshDatabase;

    private const BROWSER = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/131.0 Safari/537.36';

    public function test_a_browser_counts_once_and_drops_out_of_live_after_the_window(): void
    {
        $this->withHeader('User-Agent', self::BROWSER)->postJson('/presence')
            ->assertOk()->assertJson(['live' => 1, 'today' => 1, 'total' => 1])
            ->assertCookie(VisitorTracker::COOKIE);

        // The same browser checking in again is still one visitor; a different one adds a second.
        $tracker = app(VisitorTracker::class);
        $request = request();
        $request->headers->set('User-Agent', self::BROWSER);
        $tracker->ping($request, str_repeat('a', 32));
        $this->assertSame(['live' => 2, 'today' => 2, 'total' => 2], $tracker->ping($request, str_repeat('a', 32)));

        $this->travel(2)->minutes();
        Cache::flush();
        $this->assertSame(['live' => 0, 'today' => 2, 'total' => 2], app(VisitorTracker::class)->stats());
        $this->assertSame(2, DB::table('visitors')->whereRaw('length(visitor_hash) = 64')->count());
    }

    public function test_bots_are_not_counted(): void
    {
        $this->withHeader('User-Agent', 'Googlebot/2.1')->postJson('/presence')->assertOk()->assertJson(['total' => 0]);
    }

    public function test_activity_stream_lists_real_events_without_saying_who_voted(): void
    {
        $cat = Category::firstOrCreate(['slug' => 'ai'], ['name' => 'AI']);
        $maker = User::factory()->create();
        $voter = User::factory()->create(['name' => 'Secret Voter']);
        $product = Product::create([
            'user_id' => $maker->id, 'category_id' => $cat->id, 'name' => 'Acme', 'slug' => 'acme', 'url' => 'https://a.test',
            'tagline' => 't', 'description' => str_repeat('x', 50), 'status' => 'approved', 'launch_date' => today(), 'votes_count' => 1,
        ]);
        Vote::create(['user_id' => $voter->id, 'product_id' => $product->id]);

        $this->getJson('/live/activity.json')->assertOk()
            ->assertJsonFragment(['type' => 'vote', 'product' => 'Acme', 'text' => 'got an upvote'])
            ->assertJsonFragment(['type' => 'launch', 'product' => 'Acme'])
            ->assertDontSee('Secret Voter');

        $this->get('/')->assertOk()->assertSee('Happening right now')->assertSee('Here right now');
    }
}
