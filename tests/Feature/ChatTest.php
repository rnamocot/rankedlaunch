<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\User;
use App\Services\ChatModerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    private function send(string $body, string $chatId = '11111111-1111-4111-8111-111111111111', string $ip = '10.0.0.1')
    {
        Cache::flush(); // skip the 3-second slow mode between test messages

        return $this->withCredentials()->withCookie('chat_id', $chatId)->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/chat/messages', ['body' => $body]);
    }

    public function test_messages_are_sent_and_listed_anonymously(): void
    {
        $this->send('Hello makers, just launched my app!')->assertCreated()->assertJsonPath('message.mine', true);

        $this->getJson('/chat/messages')->assertOk()
            ->assertJsonPath('messages.0.body', 'Hello makers, just launched my app!')
            ->assertJsonMissingPath('messages.0.chat_id')
            ->assertJsonMissingPath('messages.0.ip_hash')
            ->assertJsonStructure(['messages', 'online', 'me']);

        $this->assertStringStartsWith('Maker', ChatMessage::first()->name);
    }

    public function test_rules_block_links_phones_swearing_and_sexual_talk(): void
    {
        $moderator = app(ChatModerator::class);

        foreach ([
            'check out https://spam.example', 'visit mysite.com now', 'go to www.something', 'mysite dot com',
            'call me 0917 123 4567', '+63 917-123-4567',
            'this is shit', 'f u c k this', 'sh1t happens', 'putangina mo', 'gago ka', 'send nudes',
        ] as $bad) {
            $this->assertNotNull($moderator->check($bad), "Should block: {$bad}");
        }

        foreach ([
            'I love leche flan', 'my app snaked past #3 today', 'Version 2.0 is out', 'launching at 8pm',
            'Salamat sa upvotes!', 'shipping a new feature', 'we have 120 users now',
        ] as $ok) {
            $this->assertNull($moderator->check($ok), "Should allow: {$ok}");
        }
    }

    public function test_three_blocked_attempts_mute_for_a_while(): void
    {
        $moderator = app(ChatModerator::class);
        $id = '22222222-2222-4222-8222-222222222222';

        $moderator->recordOffense($id, 'hash-x');
        $moderator->recordOffense($id, 'hash-x');
        $this->assertFalse($moderator->isMuted($id, 'hash-x'));
        $moderator->recordOffense($id, 'hash-x');
        $this->assertTrue($moderator->isMuted($id, 'hash-x'));
    }

    public function test_muted_people_cannot_send(): void
    {
        $id = '33333333-3333-4333-8333-333333333333';
        app(ChatModerator::class)->mute($id, null, now()->addHour(), 'test');

        $this->send('hello', $id)->assertForbidden();
        $this->assertSame(0, ChatMessage::count());
    }

    public function test_names_follow_the_rules(): void
    {
        $this->postJson('/chat/name', ['name' => 'IndieRenie'])->assertOk()->assertJsonPath('me', 'IndieRenie');
        $this->postJson('/chat/name', ['name' => 'a'])->assertStatus(422);
        $this->postJson('/chat/name', ['name' => 'has spaces'])->assertStatus(422);
        $this->postJson('/chat/name', ['name' => 'Admin'])->assertStatus(422);
        $this->postJson('/chat/name', ['name' => 'gago'])->assertStatus(422);
    }

    public function test_long_and_empty_messages_are_rejected(): void
    {
        $this->send(str_repeat('a', 281))->assertStatus(422);
        $this->send('   ')->assertStatus(422);
    }

    public function test_widget_shows_on_public_pages_and_can_be_turned_off(): void
    {
        $this->get('/')->assertSee('Maker lounge');

        config(['rankedlaunch.chat_enabled' => false]);
        $this->get('/')->assertDontSee('Maker lounge');
        $this->getJson('/chat/messages')->assertNotFound();
    }

    public function test_admin_can_delete_mute_and_clear(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->send('first message');
        $message = ChatMessage::first();

        $this->actingAs($admin)->get('/admin/chat')->assertOk()->assertSee('first message');
        $this->actingAs($admin)->post("/admin/chat/{$message->id}/mute")->assertRedirect();
        $this->assertTrue(app(ChatModerator::class)->isMuted($message->chat_id, $message->ip_hash));
        $this->assertSame(0, ChatMessage::count());

        $this->send('another one', '44444444-4444-4444-8444-444444444444', '10.0.0.9');
        $this->actingAs($admin)->post('/admin/chat/clear', ['confirm' => '1'])->assertRedirect();
        $this->assertSame(0, ChatMessage::count());
    }
}
