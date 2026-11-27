<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Rules for the anonymous chat, same spirit as FilRadio's:
 * be kind; swearing, sexual talk, links and phone numbers are blocked;
 * repeat offenders get muted.
 */
class ChatModerator
{
    /** English and Filipino terms. Matched as whole words or obvious variants. */
    private const BLOCKED_WORDS = [
        'fuck', 'fck', 'fuk', 'shit', 'bitch', 'asshole', 'bastard', 'cunt', 'dick', 'cock', 'pussy',
        'porn', 'sex', 'sexy', 'nude', 'nudes', 'naked', 'horny', 'slut', 'whore', 'boobs', 'tits', 'onlyfans',
        'putangina', 'putang', 'tangina', 'tang ina', 'puta', 'gago', 'ulol', 'kantot', 'pokpok',
        'puke', 'titi', 'burat', 'jakol', 'libog', 'bayag', 'hindot', 'kupal', 'tarantado',
    ];

    public const OFFENSES_BEFORE_MUTE = 3;

    /** Returns a short reason when a message breaks the rules, otherwise null. */
    public function check(string $text): ?string
    {
        if ($this->hasLink($text)) {
            return 'Links aren’t allowed in chat.';
        }
        if ($this->hasPhone($text)) {
            return 'Phone numbers aren’t allowed in chat.';
        }
        if ($this->hasBlockedWord($text)) {
            return 'Let’s keep it kind. That message wasn’t sent.';
        }

        return null;
    }

    public function validName(string $name): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9]{2,20}$/', $name)
            && ! $this->hasBlockedWord($name)
            && ! preg_match('/^(admin|moderator|mod|rankedlaunch|staff|support)/i', $name);
    }

    public function isMuted(string $chatId, string $ipHash): bool
    {
        return DB::table('chat_mutes')
            ->where('until', '>', now())
            ->where(fn ($q) => $q->where('chat_id', $chatId)->orWhere('ip_hash', $ipHash))
            ->exists();
    }

    /** Count a blocked attempt; the third within 10 minutes mutes for 30 minutes. */
    public function recordOffense(string $chatId, string $ipHash): bool
    {
        $key = "chat:offenses:{$ipHash}";
        $count = (int) Cache::get($key, 0) + 1;
        Cache::put($key, $count, now()->addMinutes(10));

        if ($count >= self::OFFENSES_BEFORE_MUTE) {
            $this->mute($chatId, $ipHash, now()->addMinutes(30), 'Automatic: repeated blocked messages');
            Cache::forget($key);

            return true;
        }

        return false;
    }

    public function mute(?string $chatId, ?string $ipHash, $until, string $reason): void
    {
        DB::table('chat_mutes')->insert([
            'chat_id' => $chatId,
            'ip_hash' => $ipHash,
            'reason' => $reason,
            'until' => $until,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function hasLink(string $text): bool
    {
        return (bool) preg_match('~(https?://|www\.)|\b[a-z0-9-]{2,}\s*(\.|dot)\s*(com|net|org|io|xyz|app|dev|co|ph|me|ly|gg|ai|so|to|link|site|store|shop|online)\b~i', $text);
    }

    private function hasPhone(string $text): bool
    {
        // 7+ digits, allowing spaces, dashes, dots and brackets between them (e.g. 0917 123 4567, +63 917-123-4567).
        return (bool) preg_match('/(\+?\d[\d\s\-().]{5,}\d)/', $text)
            && strlen(preg_replace('/\D/', '', $text)) >= 7;
    }

    private function hasBlockedWord(string $text): bool
    {
        // Normalise common tricks: f.u.c.k, f u c k, sh1t, b!tch
        $plain = strtolower($text);
        $plain = strtr($plain, ['0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a', '5' => 's', '7' => 't', '@' => 'a', '$' => 's', '!' => 'i']);
        // Letters split by symbols inside a word ("f.u.c.k", "sh*t") are joined back up.
        $plain = preg_replace('/(?<=[a-z])[.\-_*]+(?=[a-z])/', '', $plain);
        $words = preg_replace('/[^a-z ]+/', ' ', $plain);

        // Spaced-out single letters ("f u c k") become one word, so they're caught exactly.
        $words = preg_replace_callback('/\b(?:[a-z] ){2,}[a-z]\b/', fn ($m) => str_replace(' ', '', $m[0]), $words);

        foreach (self::BLOCKED_WORDS as $word) {
            if (preg_match('/\b'.preg_quote($word, '/').'\b/', $words)) {
                return true;
            }
        }

        return false;
    }
}
