<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscoveryTest extends TestCase
{
    use RefreshDatabase;

    private function make(string $name, string $category, array $attrs = []): Product
    {
        $cat = Category::firstOrCreate(['slug' => str($category)->slug()], ['name' => $category]);

        return Product::create($attrs + [
            'user_id' => User::factory()->create()->id, 'category_id' => $cat->id, 'name' => $name,
            'slug' => str($name)->slug(), 'url' => 'https://x.test', 'tagline' => "$name tagline",
            'description' => str_repeat('words ', 20), 'status' => 'approved', 'launch_date' => today(), 'pricing_type' => 'freemium',
        ]);
    }

    public function test_discover_search_and_filters(): void
    {
        $this->make('InvoiceFlow', 'Finance', ['pricing_type' => 'paid']);
        $this->make('LedgerLite', 'Finance', ['pricing_type' => 'free']);
        $this->make('ShipFast', 'Developer Tools');
        $this->make('Hidden', 'Finance', ['status' => 'pending']);

        $this->get('/discover')->assertOk()->assertSee('InvoiceFlow')->assertSee('ShipFast')->assertDontSee('Hidden');
        $this->get('/discover?q=invoice')->assertOk()->assertSee('InvoiceFlow')->assertDontSee('ShipFast');
        $this->get('/discover?category=finance&pricing=free')->assertOk()->assertSee('LedgerLite')->assertDontSee('InvoiceFlow');
        $this->get('/discover?sort=bogus')->assertRedirect();
    }

    public function test_alternatives_page_lists_same_category_and_skips_thin_pages(): void
    {
        $a = $this->make('Alpha', 'Analytics');
        $this->make('Beta', 'Analytics');
        $this->make('Gamma', 'Analytics');
        $lonely = $this->make('Solo', 'Design');

        $this->get('/apps/alpha/alternatives')->assertOk()->assertSee('Alpha alternatives')->assertSee('Beta')->assertSee('Gamma')
            ->assertSee('"@type":"ItemList"', false);
        $this->get('/apps/solo/alternatives')->assertRedirect('/apps/solo');
        $this->get('/sitemap.xml')->assertSee(url('/apps/alpha/alternatives'))->assertDontSee(url('/apps/solo/alternatives'));
    }

    public function test_png_and_dark_badges(): void
    {
        $this->make('Alpha', 'Analytics');
        $this->get('/badge/alpha.png')->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->get('/badge/alpha.svg?theme=dark')->assertOk()->assertSee('#1d1d1f', false);
    }
}
