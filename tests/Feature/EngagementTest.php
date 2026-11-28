<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Comment;
use App\Models\LaunchReminder;
use App\Models\Product;
use App\Models\User;
use App\Notifications\CommentReplied;
use App\Notifications\LaunchIsLive;
use App\Notifications\ProductCommented;
use App\Notifications\ProductUpvoted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EngagementTest extends TestCase
{
    use RefreshDatabase;

    private function product(User $maker, array $attrs = []): Product
    {
        $cat = Category::firstOrCreate(['slug' => 'ai'], ['name' => 'AI']);
        $name = $attrs['name'] ?? 'Acme';

        return Product::create($attrs + [
            'user_id' => $maker->id, 'category_id' => $cat->id, 'name' => $name, 'slug' => str($name)->slug(), 'url' => 'https://a.test',
            'tagline' => "$name tagline", 'description' => str_repeat('x', 50), 'status' => 'approved', 'launch_date' => today(), 'votes_count' => 0,
        ]);
    }

    public function test_saving_toggles_and_saved_list_is_private_to_the_owner(): void
    {
        $fan = User::factory()->create(['username' => 'fanuser']);
        $this->product(User::factory()->create());

        $this->postJson('/apps/acme/bookmark')->assertUnauthorized();
        $this->actingAs($fan)->postJson('/apps/acme/bookmark')->assertOk()->assertJson(['active' => true]);

        $this->actingAs($fan)->get('/@fanuser')->assertOk()->assertSee('Saved')->assertSee('Acme');
        $this->actingAs(User::factory()->create())->get('/@fanuser')->assertOk()->assertDontSee('Only you can see this');

        $this->actingAs($fan)->postJson('/apps/acme/bookmark')->assertOk()->assertJson(['active' => false]);
        $this->assertSame(0, $fan->bookmarks()->count());
    }

    public function test_notify_me_only_works_for_upcoming_launches_and_fires_once_on_launch_day(): void
    {
        Notification::fake();
        $fan = User::factory()->create();
        $maker = User::factory()->create();
        $this->product($maker, ['name' => 'Live Now']);
        $soon = $this->product($maker, ['name' => 'Soon', 'launch_date' => today()->addDay()]);

        $this->actingAs($fan)->postJson('/apps/live-now/remind')->assertNotFound();
        $this->actingAs($fan)->postJson('/apps/soon/remind')->assertOk()->assertJson(['active' => true]);
        $this->get('/upcoming')->assertOk()->assertSee('Soon')->assertSee('Notifying');

        // Not launch day yet: nothing sent.
        $this->artisan('launches:send-emails')->assertSuccessful();
        Notification::assertNothingSentTo($fan);

        $soon->update(['launch_date' => today()]);
        $this->artisan('launches:send-emails')->assertSuccessful();
        $this->artisan('launches:send-emails')->assertSuccessful();

        Notification::assertSentToTimes($fan, LaunchIsLive::class, 1);
        $this->assertNotNull(LaunchReminder::first()->notified_at);
    }

    public function test_comment_upvotes_sort_top_first_and_cannot_be_self_given(): void
    {
        $maker = User::factory()->create();
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $product = $this->product($maker, ['launch_date' => today()->subDay()]);
        $older = Comment::create(['product_id' => $product->id, 'user_id' => $alice->id, 'body' => 'Older comment', 'created_at' => now()->subHour()]);
        Comment::create(['product_id' => $product->id, 'user_id' => $bob->id, 'body' => 'Newer comment']);

        $this->actingAs($alice)->postJson("/comments/{$older->id}/vote")->assertStatus(422);
        $this->actingAs($bob)->postJson("/comments/{$older->id}/vote")->assertOk()->assertJson(['active' => true, 'count' => 1]);

        $this->get('/apps/acme')->assertSeeInOrder(['Older comment', 'Newer comment']);
        $this->get('/apps/acme?comments=new')->assertSeeInOrder(['Newer comment', 'Older comment']);

        $this->actingAs($bob)->postJson("/comments/{$older->id}/vote")->assertOk()->assertJson(['active' => false, 'count' => 0]);
    }

    public function test_comments_and_replies_notify_the_right_people_never_yourself(): void
    {
        Notification::fake();
        $maker = User::factory()->create();
        $fan = User::factory()->create();
        $other = User::factory()->create();
        $product = $this->product($maker);

        $this->actingAs($fan)->post('/apps/acme/comments', ['body' => 'Love it']);
        Notification::assertSentTo($maker, ProductCommented::class);
        Notification::assertNothingSentTo($fan);

        // The maker replies: the fan hears about it, the maker doesn't notify themselves.
        $parent = Comment::first();
        Cache::flush();
        $this->actingAs($maker)->post('/apps/acme/comments', ['body' => 'Thanks!', 'parent_id' => $parent->id]);
        Notification::assertSentTo($fan, CommentReplied::class);
        Notification::assertSentToTimes($maker, ProductCommented::class, 1);

        // A third person replies to the maker's reply: maker gets one "replied", not also "commented".
        Cache::flush();
        $this->actingAs($other)->post('/apps/acme/comments', ['body' => 'Same here', 'parent_id' => Comment::latest('id')->first()->id]);
        Notification::assertSentToTimes($maker, CommentReplied::class, 1);
        Notification::assertSentToTimes($maker, ProductCommented::class, 1);
        $this->assertSame($product->id, Comment::latest('id')->first()->product_id);
    }

    public function test_upvote_milestones_notify_the_maker_once(): void
    {
        $maker = User::factory()->create();
        $this->product($maker, ['votes_count' => 9]);
        $voter = User::factory()->create();

        $this->actingAs($voter)->postJson('/apps/acme/vote')->assertJson(['count' => 10]);
        $this->actingAs($voter)->postJson('/apps/acme/vote')->assertJson(['count' => 9]);
        $this->actingAs($voter)->postJson('/apps/acme/vote')->assertJson(['count' => 10]);

        $this->assertSame(1, $maker->notifications()->where('type', ProductUpvoted::class)->count());
        $this->assertStringContainsString('reached 10 upvotes', $maker->notifications()->first()->data['message']);
    }

    public function test_notifications_page_lists_and_marks_read(): void
    {
        $maker = User::factory()->create();
        $product = $this->product($maker);
        $maker->notify(new ProductUpvoted($product, 1));

        $this->get('/notifications')->assertRedirect('/login');
        $this->actingAs($maker)->get('/')->assertSee('Notifications (1 unread)');
        $this->actingAs($maker)->get('/notifications')->assertOk()->assertSee('Acme got its first upvote');
        $this->assertSame(0, $maker->unreadNotifications()->count());
    }

    public function test_instant_search_returns_apps_categories_and_makers(): void
    {
        $maker = User::factory()->create(['name' => 'Acme Founder']);
        $this->product($maker);
        $this->product($maker, ['name' => 'Hidden Acme', 'status' => 'pending']);

        $this->getJson('/search.json?q=a')->assertOk()->assertJson(['apps' => []]);
        $this->getJson('/search.json?q=acme')->assertOk()
            ->assertJsonCount(1, 'apps')->assertJsonPath('apps.0.name', 'Acme')
            ->assertJsonPath('makers.0.name', 'Acme Founder');
        $this->getJson('/search.json?q=ai')->assertOk()->assertJsonPath('categories.0.name', 'AI');
    }

    public function test_streaks_count_consecutive_days_and_reset_after_a_gap(): void
    {
        $user = User::factory()->create();

        $this->travelTo(today()->addHours(10));
        $this->actingAs($user)->get('/');
        $this->travel(1)->days();
        $this->actingAs($user)->get('/');
        $this->assertSame(2, $user->fresh()->currentStreak());

        $this->travel(3)->days();
        $this->assertSame(0, $user->fresh()->currentStreak());
        $this->actingAs($user)->get('/');
        $this->assertSame(1, $user->fresh()->streak_days);
        $this->assertSame(2, $user->fresh()->longest_streak);
    }

    public function test_leaderboard_ranks_makers_by_upvotes_this_month(): void
    {
        $this->product(User::factory()->create(['name' => 'Small Maker']), ['name' => 'Small', 'votes_count' => 3]);
        $this->product(User::factory()->create(['name' => 'Big Maker']), ['name' => 'Big', 'votes_count' => 30]);

        $this->get('/makers')->assertOk()->assertSeeInOrder(['Big Maker', 'Small Maker']);
        $this->get('/makers/all-time')->assertOk();
        $this->get('/makers/forever')->assertNotFound();
    }
}
