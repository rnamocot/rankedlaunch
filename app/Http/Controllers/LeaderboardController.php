<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/** Top makers (upvotes earned), top supporters (upvotes given) and active streaks. */
class LeaderboardController extends Controller
{
    public const PERIODS = ['month' => 'This month', 'all-time' => 'All time'];

    public function show(string $period = 'month'): View
    {
        abort_unless(array_key_exists($period, self::PERIODS), 404);

        $from = $period === 'month' ? today()->startOfMonth() : Carbon::create(2000);

        return view('leaderboard', [
            'period' => $period,
            'periods' => self::PERIODS,
            'makers' => self::topMakers($from),
            'supporters' => User::whereNull('banned_at')
                ->withCount(['votes as given' => fn ($q) => $q->where('created_at', '>=', $from)])
                ->orderByDesc('given')->orderBy('id')
                ->limit(10)->get()
                ->where('given', '>', 0)->values(),
            'streaks' => User::whereNull('banned_at')
                ->whereDate('streak_last_date', '>=', today()->subDay())
                ->where('streak_days', '>', 1)
                ->orderByDesc('streak_days')->orderBy('id')
                ->limit(10)->get(),
        ]);
    }

    /** Makers ranked by upvotes on their live launches since $from. */
    public static function topMakers(Carbon $from, int $limit = 20): Collection
    {
        return User::whereNull('banned_at')
            ->withSum(['products as earned' => fn ($q) => $q->launchedBetween($from, today())], 'votes_count')
            ->withCount(['products as launches' => fn ($q) => $q->launchedBetween($from, today())])
            ->orderByDesc('earned')->orderBy('id')
            ->limit($limit)->get()
            ->filter(fn (User $u) => $u->launches > 0)->values();
    }
}
