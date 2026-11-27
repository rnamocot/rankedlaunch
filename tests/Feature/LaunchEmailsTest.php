<?php

namespace Tests\Feature;

use App\Mail\LaunchEmail;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LaunchEmailsTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $attrs = [], ?User $user = null): Product
    {
        $user ??= User::factory()->create();
        $category = Category::firstOrCreate(['slug' => 'ai'], ['name' => 'AI']);

        return Product::create($attrs + [
            'user_id' => $user->id, 'category_id' => $category->id, 'name' => 'Acme '.uniqid(),
            'slug' => 'acme-'.uniqid(), 'url' => 'https://acme.test', 'tagline' => 'Does things',
            'description' => str_repeat('x', 50), 'status' => 'draft',
        ]);
    }

    private function sentTypes(): array
    {
        return Mail::sent(LaunchEmail::class)->map->type->all();
    }

    public function test_booking_free_launch_sends_booked_email_once(): void
    {
        Mail::fake();
        $p = $this->product();

        $this->actingAs($p->user)->post("/submit/{$p->slug}/launch", ['plan' => 'free']);
        $this->actingAs($p->user)->post("/submit/{$p->slug}/launch", ['plan' => 'free']); // re-submit keeps slot, no new email

        $this->assertSame(['booked'], $this->sentTypes());
        Mail::assertSent(LaunchEmail::class, fn ($m) => $m->hasTo($p->user->email));
    }

    public function test_paid_launch_sends_booked_email(): void
    {
        Mail::fake();
        $p = $this->product();
        $this->actingAs($p->user)->post("/submit/{$p->slug}/launch", ['plan' => 'pro', 'launch_date' => today()->addDays(3)->toDateString()]);
        $payment = \App\Models\Payment::first();
        $this->actingAs($p->user)->post("/checkout/{$payment->id}/simulate");

        $this->assertSame(['booked'], $this->sentTypes());
    }

    public function test_admin_approve_and_reject_send_emails(): void
    {
        Mail::fake();
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $a = $this->product(['status' => 'pending', 'launch_date' => today()->addDays(2)]);
        $b = $this->product(['status' => 'pending', 'launch_date' => today()->addDays(2)]);

        $this->actingAs($admin)->post("/admin/products/{$a->slug}/approve");
        $this->actingAs($admin)->post("/admin/products/{$a->slug}/approve"); // no duplicate
        $this->actingAs($admin)->post("/admin/products/{$b->slug}/reject", ['reason' => 'Site is down']);

        $this->assertSame(['approved', 'rejected'], $this->sentTypes());
    }

    public function test_scheduled_emails_go_out_once_each(): void
    {
        Mail::fake();
        $this->product(['status' => 'approved', 'launch_date' => today()->addDay()]);
        $this->product(['status' => 'approved', 'launch_date' => today()]);
        $this->product(['status' => 'approved', 'launch_date' => today()->subDay(), 'votes_count' => 12]);
        $this->product(['status' => 'pending', 'launch_date' => today()]); // not approved: skipped

        $this->artisan('launches:send-emails')->assertSuccessful();
        $this->artisan('launches:send-emails')->assertSuccessful();

        $types = $this->sentTypes();
        sort($types);
        $this->assertSame(['live', 'results', 'tomorrow'], $types);
    }

    public function test_placeholder_emails_are_skipped(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'x_123@users.rankedlaunch.com']);
        $this->product(['status' => 'approved', 'launch_date' => today()], $user);

        $this->artisan('launches:send-emails');
        Mail::assertNothingSent();
    }

    public function test_every_email_type_renders(): void
    {
        $p = $this->product(['status' => 'approved', 'launch_date' => today()->subDay(), 'votes_count' => 30, 'rejection_reason' => 'Broken link']);

        foreach (LaunchEmail::TYPES as $type) {
            $html = (new LaunchEmail($p, $type))->render();
            $this->assertStringContainsString($p->name, $html, $type);
        }
    }

    public function test_share_images_render_as_png(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $p = $this->product(['status' => 'approved', 'launch_date' => today(), 'logo' => null]);
        \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response('', 404)]);

        $this->get("/og/{$p->slug}.png")->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->get('/og.png')->assertOk();

        $draft = $this->product();
        $this->get("/og/{$draft->slug}.png")->assertNotFound();
    }

    public function test_emails_show_launch_time_in_the_makers_time_zone(): void
    {
        $maker = User::factory()->create(['timezone' => 'Asia/Manila']);
        $p = $this->product(['status' => 'approved', 'launch_date' => today()->addDay()], $maker);

        $html = (new LaunchEmail($p, 'tomorrow'))->render();
        $this->assertStringContainsString('8:00 AM (Manila time)', $html);

        $noZone = $this->product(['status' => 'approved', 'launch_date' => today()->addDay()]);
        $this->assertStringContainsString('00:00 UTC', (new LaunchEmail($noZone, 'tomorrow'))->render());
    }

    public function test_browser_time_zone_is_saved_for_signed_in_makers(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/account/timezone', ['timezone' => 'Asia/Manila'])->assertNoContent();
        $this->assertSame('Asia/Manila', $user->fresh()->timezone);
        $this->actingAs($user)->postJson('/account/timezone', ['timezone' => 'Mars/Base'])->assertStatus(422);
    }
}
