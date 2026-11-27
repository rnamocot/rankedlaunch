<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ChatMessage;
use App\Models\Product;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardChartsTest extends TestCase
{
    use RefreshDatabase;

    private function product(User $user, array $attrs = []): Product
    {
        $cat = Category::firstOrCreate(['slug' => 'ai'], ['name' => 'AI']);

        return Product::create($attrs + [
            'user_id' => $user->id, 'category_id' => $cat->id, 'name' => 'P'.uniqid(), 'slug' => 'p-'.uniqid(),
            'url' => 'https://x.test', 'tagline' => 't', 'description' => str_repeat('x', 50),
            'status' => 'approved', 'plan' => 'free', 'launch_date' => today(),
        ]);
    }

    public function test_views_and_clicks_are_recorded_per_day(): void
    {
        $p = $this->product(User::factory()->create());

        $this->get("/apps/{$p->slug}");
        $this->post("/apps/{$p->slug}/click");

        $row = DB::table('product_daily_stats')->where('product_id', $p->id)->first();
        $this->assertSame([1, 1], [(int) $row->views, (int) $row->clicks]);
    }

    public function test_dashboard_shows_kpis_charts_and_table(): void
    {
        $user = User::factory()->create();
        $p = $this->product($user, ['name' => 'ChartApp', 'votes_count' => 1]);
        Vote::create(['user_id' => User::factory()->create()->id, 'product_id' => $p->id]);
        DB::table('product_daily_stats')->insert(['product_id' => $p->id, 'date' => today()->toDateString(), 'views' => 12, 'clicks' => 4]);

        $this->actingAs($user)->get('/dashboard')->assertOk()
            ->assertSee('Upvotes')->assertSee('Page views')->assertSee('Visits to your sites')
            ->assertSee('data-chart', false)
            ->assertSee('ChartApp')->assertSee('Manage')
            ->assertSee('this week');
    }

    public function test_maker_can_delete_own_listing_but_not_others(): void
    {
        $user = User::factory()->create();
        $mine = $this->product($user);
        $theirs = $this->product(User::factory()->create());

        $this->actingAs($user)->get("/dashboard/{$mine->slug}")->assertSee('Delete this listing');
        $this->actingAs($user)->delete("/dashboard/{$mine->slug}")->assertSessionHasErrors('confirm');
        $this->actingAs($user)->delete("/dashboard/{$mine->slug}", ['confirm' => '1'])->assertRedirect('/dashboard');
        $this->assertModelMissing($mine);

        $this->actingAs($user)->delete("/dashboard/{$theirs->slug}", ['confirm' => '1'])->assertForbidden();
        $this->assertModelExists($theirs);
    }

    public function test_chat_keeps_only_the_latest_50_messages(): void
    {
        foreach (range(1, 52) as $i) {
            Cache::flush();
            $this->withCredentials()->withCookie('chat_id', '77777777-7777-4777-8777-777777777777')
                ->postJson('/chat/messages', ['body' => "message number {$i}"])->assertCreated();
        }

        $this->assertSame(50, ChatMessage::count());
        $this->assertSame('message number 3', ChatMessage::orderBy('id')->first()->body);
        $this->assertCount(50, $this->getJson('/chat/messages')->json('messages'));
    }

    public function test_homepage_has_the_flip_clock(): void
    {
        $this->get('/')->assertOk()->assertSee('data-flip-clock', false)->assertSee('voting closes in', false);
    }

    public function test_drafts_can_be_deleted_from_the_dashboard_and_go_live_step(): void
    {
        $user = User::factory()->create();
        $draft = $this->product($user, ['status' => 'draft', 'launch_date' => null, 'name' => 'HalfDone']);

        $this->actingAs($user)->get('/dashboard')->assertSee('Delete draft');
        $this->actingAs($user)->get("/submit/{$draft->slug}/launch")->assertSee('Discard this draft');

        $this->actingAs($user)->delete("/dashboard/{$draft->slug}", ['confirm' => '1'])
            ->assertRedirect('/dashboard')->assertSessionHas('status', 'Draft “HalfDone” discarded.');
        $this->assertModelMissing($draft);
    }
}
