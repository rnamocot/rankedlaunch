<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportAppsTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_founder_apps_once_under_the_owner(): void
    {
        $this->seed(CategorySeeder::class);
        $owner = User::factory()->create(['email' => 'founder@example.com']);

        $this->artisan('rankedlaunch:import-apps', ['email' => 'founder@example.com'])->assertSuccessful();
        $this->assertSame(8, Product::where('user_id', $owner->id)->count());
        $this->assertSame(2, Product::live()->whereDate('launch_date', today())->count());
        $this->assertTrue(Product::firstWhere('slug', 'clickverve')->is_dofollow);
        $this->assertSame(0, (int) Product::sum('votes_count'));

        // Second run adds nothing
        $this->artisan('rankedlaunch:import-apps', ['email' => 'founder@example.com'])->assertSuccessful();
        $this->assertSame(8, Product::count());

        $this->get('/')->assertOk()->assertSee('ClickVerve')->assertSee('Zendz Tools');
        $this->get('/apps/clickverve')->assertOk()->assertSee('clickverveinteractive.com');
    }

    public function test_unknown_owner_fails(): void
    {
        $this->artisan('rankedlaunch:import-apps', ['email' => 'nobody@example.com'])->assertFailed();
    }
}
