<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Anonymous presence: each browser gets a random cookie, stored only as a hash.
 * A tab checks in every 30 seconds while visible; "here now" counts check-ins in the last 75 seconds.
 */
class VisitorTracker
{
    public const COOKIE = 'rl_vid';

    public const LIVE_WINDOW_SECONDS = 75;

    /** Record a check-in for this browser and return the fresh stats. */
    public function ping(Request $request, string $visitorId): array
    {
        if (! $this->isBot($request)) {
            $now = now();
            DB::table('visitors')->upsert(
                [['visitor_hash' => hash('sha256', $visitorId), 'first_seen_at' => $now, 'last_seen_at' => $now]],
                ['visitor_hash'],
                ['last_seen_at'],
            );
            Cache::forget('visitor-stats');
        }

        return $this->stats();
    }

    /**
     * @return array{live: int, today: int, total: int}
     */
    public function stats(): array
    {
        return Cache::remember('visitor-stats', 5, fn () => [
            'live' => DB::table('visitors')->where('last_seen_at', '>=', now()->subSeconds(self::LIVE_WINDOW_SECONDS))->count(),
            'today' => DB::table('visitors')->where('last_seen_at', '>=', today())->count(),
            'total' => DB::table('visitors')->count(),
        ]);
    }

    /** The browser's visitor id from its cookie, or a new random one. */
    public function visitorId(Request $request): string
    {
        $id = (string) $request->cookie(self::COOKIE);

        return preg_match('/^[A-Za-z0-9]{32}$/', $id) ? $id : Str::random(32);
    }

    private function isBot(Request $request): bool
    {
        return (bool) preg_match('/bot|crawl|spider|slurp|preview|facebookexternalhit|curl|wget|headless|lighthouse/i', (string) $request->userAgent());
    }
}
