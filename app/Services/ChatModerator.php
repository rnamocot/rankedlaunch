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

    /** Insults, harassment, threats and hate terms. Phrases are matched as whole words too. */
    private const TOXIC_WORDS = [
        'idiot', 'stupid', 'moron', 'dumbass', 'retard', 'retarded', 'loser', 'scammer',
        'kys', 'kill yourself', 'kill urself', 'go die', 'die bitch', 'i will kill', 'ill kill you', 'hope you die',
        'nigger', 'nigga', 'faggot', 'fag', 'tranny', 'chink', 'spic', 'kike', 'nazi',
        'bobo', 'tanga', 'inutil', 'engot', 'shunga', 'pakyu', 'pakshet', 'hayop ka', 'mamatay ka',
        'papatayin kita', 'walang kwenta', 'bwisit ka', 'ulaga',
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
        if ($this->hasWord($text, self::BLOCKED_WORDS) || $this->hasWord($text, self::TOXIC_WORDS)) {
            return 'Let’s keep it kind. That message wasn’t sent.';
        }

        return null;
    }

    /** Spam-like messages: refused, but not counted as an offense. */
    public function spamReason(string $text): ?string
    {
        if (preg_match('/(.)\1{6,}/u', $text)) {
            return 'Too many repeated characters.';
        }

        $letters = preg_replace('/[^A-Za-z]/', '', $text);
        if (strlen($letters) >= 12 && strlen(preg_replace('/[^A-Z]/', '', $letters)) / strlen($letters) > 0.8) {
            return 'Please don’t shout.';
        }

        return null;
    }

    public function validName(string $name): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9]{2,20}$/', $name)
            && ! $this->hasWord($name, self::BLOCKED_WORDS)
            && ! $this->hasWord($name, self::TOXIC_WORDS)
            && ! preg_match('/^(admin|moderator|mod|rankedlaunch|staff|support)/i', $name);
    }

    public function isBanned(string $chatId, string $ipHash): bool
    {
        return DB::table('chat_mutes')
            ->where('until', '>', now()->addYears(50))
            ->where(fn ($q) => $q->where('chat_id', $chatId)->orWhere('ip_hash', $ipHash))
            ->exists();
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
            $repeat = DB::table('chat_mutes')
                ->where('ip_hash', $ipHash)
                ->where('reason', 'like', 'Automatic%')
                ->where('created_at', '>=', now()->subDay())
                ->exists();

            $repeat
                ? $this->mute($chatId, $ipHash, now()->addDays(7), 'Automatic: muted again within 24 hours')
                : $this->mute($chatId, $ipHash, now()->addMinutes(30), 'Automatic: repeated blocked messages');
            Cache::forget($key);

            return true;
        }

        return false;
    }

    /** A ban is a mute that doesn't run out (100 years). */
    public function ban(?string $chatId, ?string $ipHash, string $reason = 'Banned by admin'): void
    {
        $this->mute($chatId, $ipHash, now()->addYears(100), $reason);
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

    private function hasWord(string $text, array $list): bool
    {
        // Normalise common tricks: f.u.c.k, f u c k, sh1t, b!tch
        $plain = strtolower($text);
        $plain = strtr($plain, ['0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a', '5' => 's', '7' => 't', '@' => 'a', '$' => 's', '!' => 'i']);
        // Letters split by symbols inside a word ("f.u.c.k", "sh*t") are joined back up.
        $plain = preg_replace('/(?<=[a-z])[.\-_*]+(?=[a-z])/', '', $plain);
        $words = preg_replace('/[^a-z ]+/', ' ', $plain);

        // Spaced-out single letters ("f u c k") become one word, so they're caught exactly.
        $words = preg_replace_callback('/\b(?:[a-z] ){2,}[a-z]\b/', fn ($m) => str_replace(' ', '', $m[0]), $words);

        foreach ($list as $word) {
            if (preg_match('/\b'.preg_quote($word, '/').'\b/', $words)) {
                return true;
            }
        }

        return false;
    }
}
