<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaunchOfferTest extends TestCase
{
    use RefreshDatabase;

    private function draftProduct(User $user): Product
    {
        $category = Category::firstOrCreate(['slug' => 'ai'], ['name' => 'AI']);

        return Product::create([
            'user_id' => $user->id, 'category_id' => $category->id, 'name' => 'P'.uniqid(),
            'slug' => 'p-'.uniqid(), 'url' => 'https://p.test', 'tagline' => 'x',
            'description' => str_repeat('x', 50), 'status' => 'draft',
        ]);
    }

    private function buyPro(User $user): Payment
    {
        $product = $this->draftProduct($user);
        $this->actingAs($user)->post("/submit/{$product->slug}/launch", [
            'plan' => 'pro', 'launch_date' => today()->addDays(5)->toDateString(),
        ]);

        return Payment::latest('id')->first();
    }

    public function test_first_buyers_get_offer_price_then_it_returns_to_normal(): void
    {
        config(['rankedlaunch.promo.limit' => 2, 'rankedlaunch.paid_slots_per_day' => 100]);
        $user = User::factory()->create();

        $this->get('/pricing')->assertSee('$20')->assertSee('$39')->assertSee('2 of 2 left');

        $first = $this->buyPro($user);
        $this->assertSame(2000, $first->amount);
        $this->assertTrue($first->is_promo);
        $this->actingAs($user)->post("/checkout/{$first->id}/simulate");

        $second = $this->buyPro($user);
        $this->assertSame(2000, $second->amount);
        $this->actingAs($user)->post("/checkout/{$second->id}/simulate");

        // Offer is used up: back to the regular price.
        $third = $this->buyPro($user);
        $this->assertSame(3900, $third->amount);
        $this->assertFalse($third->is_promo);

        $this->get('/pricing')->assertDontSee('launch-offer spots left');
    }

    public function test_abandoned_checkouts_release_their_spot_after_30_minutes(): void
    {
        config(['rankedlaunch.promo.limit' => 1, 'rankedlaunch.paid_slots_per_day' => 100]);
        $user = User::factory()->create();

        $held = $this->buyPro($user);
        $this->assertSame(2000, $held->amount);
        $this->assertSame(3900, $this->buyPro($user)->amount); // spot is held

        Payment::query()->update(['created_at' => now()->subHour()]);
        $this->assertSame(2000, $this->buyPro($user)->amount); // released
    }
}
