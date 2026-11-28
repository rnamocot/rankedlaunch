<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\LogoFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LogoFetcherTest extends TestCase
{
    use RefreshDatabase;

    private function png(int $size): string
    {
        $image = imagecreatetruecolor($size, $size);
        ob_start();
        imagepng($image);

        return ob_get_clean();
    }

    private function listing(string $url): Product
    {
        return Product::create([
            'user_id' => User::factory()->create()->id, 'name' => 'Acme', 'slug' => 'acme', 'url' => $url,
            'tagline' => 't', 'description' => str_repeat('x', 50), 'status' => 'approved', 'launch_date' => today(), 'source' => 'hn', 'source_id' => '1',
        ]);
    }

    public function test_uses_the_sites_apple_touch_icon(): void
    {
        Storage::fake('public');
        Http::fake([
            'acme.test/icons/apple.png' => Http::response($this->png(180), 200, ['Content-Type' => 'image/png']),
            'acme.test' => Http::response('<html><head><link rel="icon" href="/favicon.ico"><link rel="apple-touch-icon" sizes="180x180" href="/icons/apple.png"></head></html>'),
            '*' => Http::response('', 404),
        ]);

        $path = app(LogoFetcher::class)->fetch($this->listing('https://acme.test'));

        $this->assertStringEndsWith('.png', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_falls_back_to_a_monogram_when_only_tiny_or_missing_icons_exist(): void
    {
        Storage::fake('public');
        Http::fake([
            'acme.test/apple-touch-icon.png' => Http::response($this->png(16), 200, ['Content-Type' => 'image/png']),
            'acme.test' => Http::response('<html></html>'),
            '*' => Http::response('', 404), // Google: no favicon known
        ]);

        $path = app(LogoFetcher::class)->fetch($this->listing('https://acme.test'));

        $this->assertSame('logos/acme-monogram.svg', $path);
        $this->assertStringContainsString('>A</text>', Storage::disk('public')->get($path));
    }

    public function test_github_repos_use_the_owners_avatar_not_githubs_icon(): void
    {
        $this->assertSame(['https://github.com/someone.png?size=200'], app(LogoFetcher::class)->candidates('https://github.com/someone/sim-drop'));
    }
}
