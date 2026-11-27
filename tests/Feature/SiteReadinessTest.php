<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SiteReadinessTest extends TestCase
{
    use RefreshDatabase;

    private function liveProduct(): Product
    {
        $category = Category::create(['name' => 'AI Tools', 'slug' => 'ai-tools']);
        $user = User::factory()->create();

        return Product::create([
            'user_id' => $user->id, 'category_id' => $category->id, 'name' => 'Acme',
            'slug' => 'acme', 'url' => 'https://acme.test', 'tagline' => 'Does things',
            'description' => str_repeat('x', 50), 'status' => 'approved', 'launch_date' => today(), 'votes_count' => 5,
        ]);
    }

    public function test_robots_welcomes_ai_crawlers_and_links_sitemap(): void
    {
        $this->get('/robots.txt')->assertOk()
            ->assertSee('User-agent: GPTBot')->assertSee('User-agent: ClaudeBot')->assertSee('User-agent: PerplexityBot')
            ->assertSee('Disallow: /admin')->assertSee('Sitemap: '.url('/sitemap.xml'));
    }

    public function test_llms_txt_feed_and_json_list_launches(): void
    {
        $this->liveProduct();

        $this->get('/llms.txt')->assertOk()->assertSee('# RankedLaunch')->assertSee('Acme');
        $this->get('/feed.xml')->assertOk()->assertSee('<rss', false)->assertSee('Acme');
        $this->getJson('/launches.json')->assertOk()->assertJsonPath('today.0.name', 'Acme')->assertJsonPath('today.0.upvotes', 5);
    }

    public function test_pages_have_structured_data_and_share_images(): void
    {
        $this->liveProduct();

        $this->get('/')->assertOk()->assertSee('"@type":"Organization"', false)->assertSee('og.png', false)->assertSee('summary_large_image', false);
        $this->get('/apps/acme')->assertOk()->assertSee('"@type":"SoftwareApplication"', false)->assertSee('"@type":"BreadcrumbList"', false)->assertSee('/og/acme.png', false);
        $this->get('/category/ai-tools')->assertOk()->assertSee('"@type":"ItemList"', false);
        $this->get('/rankings/week')->assertOk()->assertSee('"@type":"ItemList"', false);
    }

    public function test_legal_and_about_pages_render_and_are_in_sitemap(): void
    {
        foreach (['/about', '/terms', '/privacy', '/refunds'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/sitemap.xml')->assertOk()->assertSee(url('/terms'))->assertSee(url('/launch'));
    }

    public function test_custom_404(): void
    {
        $this->get('/apps/does-not-exist')->assertNotFound()->assertSee('This page didn’t make the board.');
    }

    public function test_security_headers(): void
    {
        $this->get('/')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'SAMEORIGIN')->assertHeader('Referrer-Policy');
    }

    public function test_newsletter_signup_dedupes_and_blocks_bots(): void
    {
        $this->post('/subscribe', ['email' => 'Fan@Example.com', 'source' => 'home'])->assertRedirect();
        $this->post('/subscribe', ['email' => 'fan@example.com'])->assertRedirect();
        $this->post('/subscribe', ['email' => 'bot@example.com', 'website' => 'spam'])->assertSessionHasErrors('website');

        $this->assertSame(['fan@example.com'], Subscriber::pluck('email')->all());

        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin)->get('/admin/subscribers.csv')->assertOk()->assertDownload('rankedlaunch-subscribers.csv');
    }

    public function test_password_reset_flow(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'maker@example.com']);

        $this->get('/forgot-password')->assertOk();
        $this->post('/forgot-password', ['email' => 'maker@example.com'])->assertSessionHas('status');
        $this->post('/forgot-password', ['email' => 'nobody@example.com'])->assertSessionHas('status'); // same reply

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($n) use (&$token) {
            $token = $n->token;

            return true;
        });

        $this->get("/reset-password/{$token}?email=maker@example.com")->assertOk();
        $this->post('/reset-password', [
            'token' => $token, 'email' => 'maker@example.com',
            'password' => 'new-password-1', 'password_confirmation' => 'new-password-1',
        ])->assertRedirect('/login');

        $this->post('/login', ['email' => 'maker@example.com', 'password' => 'new-password-1'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }
}
