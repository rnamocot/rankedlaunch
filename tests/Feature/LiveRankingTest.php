<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LiveRankingTest extends TestCase
{
    use RefreshDatabase;

    private function launch(string $name, int $votes): Product
    {
        $cat = Category::firstOrCreate(['slug' => 'ai'], ['name' => 'AI']);

        return Product::create([
            'user_id' => User::factory()->create()->id, 'category_id' => $cat->id, 'name' => $name,
            'slug' => str($name)->slug(), 'url' => 'https://x.test', 'tagline' => "$name tagline",
            'description' => str_repeat('x', 50), 'status' => 'approved', 'launch_date' => today(), 'votes_count' => $votes,
        ]);
    }

    public function test_banner_uses_real_launches_when_there_are_enough(): void
    {
        $this->launch('Alpha', 5);
        $this->launch('Beta', 9);
        $this->launch('Gamma', 1);

        $this->get('/')->assertOk()
            ->assertSee('data-rank-live', false)
            ->assertSee('Live ranking')
            ->assertDontSee('How ranking works')
            ->assertSeeInOrder(['Beta', 'Alpha', 'Gamma']);
    }

    public function test_banner_is_an_honestly_labelled_demo_when_board_is_thin(): void
    {
        $this->launch('Alpha', 5);

        $this->get('/')->assertOk()
            ->assertSee('How ranking works')
            ->assertDontSee('data-rank-live', false);
    }

    public function test_live_feed_is_ranked_and_reflects_new_votes(): void
    {
        $a = $this->launch('Alpha', 5);
        $this->launch('Beta', 9);

        $this->getJson('/live/today.json')->assertOk()
            ->assertJsonPath('top.0.name', 'Beta')
            ->assertJsonPath('top.1.name', 'Alpha');

        $a->update(['votes_count' => 12]);
        Cache::forget('live-today');

        $this->getJson('/live/today.json')->assertJsonPath('top.0.name', 'Alpha')->assertJsonPath('top.0.votes', 12);
    }
}
