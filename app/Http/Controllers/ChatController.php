<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Services\ChatModerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * Anonymous global chat ("Maker lounge").
 * Identity is a random id + a display name kept in long-lived cookies; no account needed.
 * The browser polls for new messages every few seconds while the panel is open.
 */
class ChatController extends Controller
{
    private const HISTORY = 60;          // messages shown when the panel opens
    private const ONLINE_WINDOW = 60;    // seconds since last poll that counts as "online"

    public function index(Request $request)
    {
        abort_unless(config('rankedlaunch.chat_enabled'), 404);

        [$chatId, $name] = $this->identity($request);
        $this->touchPresence($chatId);

        $after = (int) $request->query('after', 0);
        $messages = ChatMessage::query()
            ->when($after > 0, fn ($q) => $q->where('id', '>', $after)->orderBy('id')->limit(100),
                fn ($q) => $q->latest('id')->limit(self::HISTORY))
            ->get()
            ->sortBy('id')
            ->values()
            ->map(fn (ChatMessage $m) => $m->toPublic($chatId));

        return $this->withIdentity(response()->json([
            'messages' => $messages,
            'online' => $this->onlineCount(),
            'me' => $name,
        ]), $chatId, $name);
    }

    public function store(Request $request, ChatModerator $moderator)
    {
        abort_unless(config('rankedlaunch.chat_enabled'), 404);

        [$chatId, $name] = $this->identity($request);
        $ipHash = $this->ipHash($request);

        $body = trim(preg_replace('/\s+/', ' ', (string) $request->input('body')));
        if ($body === '' || mb_strlen($body) > 280) {
            return response()->json(['error' => 'Messages can be 1 to 280 characters.'], 422);
        }

        if ($moderator->isMuted($chatId, $ipHash)) {
            return response()->json(['error' => 'You’re muted for a while for breaking the chat rules.'], 403);
        }

        // One message every 3 seconds per person.
        $slowKey = "chat:slow:{$ipHash}";
        if (Cache::has($slowKey)) {
            return response()->json(['error' => 'Slow down a little.'], 429);
        }

        if ($reason = $moderator->check($body)) {
            $muted = $moderator->recordOffense($chatId, $ipHash);

            return response()->json(['error' => $muted ? 'Muted for 30 minutes after repeated blocked messages.' : $reason], 422);
        }

        Cache::put($slowKey, true, now()->addSeconds(3));

        $message = ChatMessage::create([
            'name' => $name,
            'body' => $body,
            'chat_id' => $chatId,
            'ip_hash' => $ipHash,
        ]);

        // Keep the table small: occasionally drop messages older than 14 days.
        if (random_int(1, 50) === 1) {
            ChatMessage::where('created_at', '<', now()->subDays(14))->delete();
        }

        return $this->withIdentity(response()->json(['message' => $message->toPublic($chatId)], 201), $chatId, $name);
    }

    public function rename(Request $request, ChatModerator $moderator)
    {
        abort_unless(config('rankedlaunch.chat_enabled'), 404);

        [$chatId] = $this->identity($request);
        $name = trim((string) $request->input('name'));

        if (! $moderator->validName($name)) {
            return response()->json(['error' => '2 to 20 letters or numbers. Rude names are blocked.'], 422);
        }

        return $this->withIdentity(response()->json(['me' => $name]), $chatId, $name);
    }

    /* ---------- helpers ---------- */

    /** [chat id, display name] from cookies, creating them on first visit. */
    private function identity(Request $request): array
    {
        $chatId = $request->cookie('chat_id');
        if (! is_string($chatId) || ! Str::isUuid($chatId)) {
            $chatId = (string) Str::uuid();
        }

        $name = $request->cookie('chat_name');
        if (! is_string($name) || ! preg_match('/^[A-Za-z0-9]{2,20}$/', $name)) {
            $name = 'Maker'.random_int(1000, 9999);
        }

        return [$chatId, $name];
    }

    private function withIdentity($response, string $chatId, string $name)
    {
        $year = 60 * 24 * 365;

        return $response
            ->withCookie(Cookie::make('chat_id', $chatId, $year, sameSite: 'lax'))
            ->withCookie(Cookie::make('chat_name', $name, $year, sameSite: 'lax'));
    }

    /** Salted hash: lets us mute repeat offenders without storing anyone's IP. */
    private function ipHash(Request $request): string
    {
        return hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));
    }

    private function touchPresence(string $chatId): void
    {
        $seen = Cache::get('chat:presence', []);
        $cutoff = time() - self::ONLINE_WINDOW;
        $seen = array_filter($seen, fn ($t) => $t >= $cutoff);
        $seen[$chatId] = time();
        Cache::put('chat:presence', $seen, now()->addMinutes(5));
    }

    public function onlineCount(): int
    {
        $cutoff = time() - self::ONLINE_WINDOW;

        return max(1, count(array_filter(Cache::get('chat:presence', []), fn ($t) => $t >= $cutoff)));
    }
}
