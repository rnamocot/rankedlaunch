<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Carbon;

/**
 * Launch calendar capacity.
 *  - free_slots_per_day:     free launches per day
 *  - paid_slots_per_day:     Listing + Pro launches per day (shared)
 *  - featured_slots_per_day: Pro launches per day (inside the paid cap), so the
 *                            homepage Featured rotation never gets crowded.
 */
class LaunchScheduler
{
    /** Statuses that hold a slot in the calendar. */
    private const HOLDING = ['pending', 'approved'];

    public function nextFreeDate(): Carbon
    {
        $date = today()->addDays(config('rankedlaunch.free_wait_days'));
        $limit = config('rankedlaunch.free_slots_per_day');

        for ($i = 0; $i < 730; $i++) {
            if ($this->countOn($date, 'free') < $limit) {
                return $date;
            }
            $date = $date->copy()->addDay();
        }

        return $date;
    }

    /** Dates a paid launch can pick: paid slots left, and Pro slots left. */
    public function paidDates(): array
    {
        $days = config('rankedlaunch.paid_max_days_ahead');
        $paidCap = config('rankedlaunch.paid_slots_per_day');
        $proCap = config('rankedlaunch.featured_slots_per_day');

        // One query for the whole range instead of one per day.
        $counts = Product::whereIn('status', self::HOLDING)
            ->where('plan', '!=', 'free')
            ->whereDate('launch_date', '>', today())
            ->whereDate('launch_date', '<=', today()->addDays($days))
            ->selectRaw("DATE(launch_date) as day, COUNT(*) as paid, SUM(CASE WHEN plan = 'featured' THEN 1 ELSE 0 END) as pro")
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $dates = [];
        for ($i = 1; $i <= $days; $i++) {
            $date = today()->addDays($i);
            $row = $counts[$date->toDateString()] ?? null;
            $left = max(0, $paidCap - (int) ($row->paid ?? 0));
            $dates[] = [
                'date' => $date,
                'left' => $left,
                'pro_left' => min($left, max(0, $proCap - (int) ($row->pro ?? 0))),
            ];
        }

        return $dates;
    }

    public function paidDateAvailable(Carbon $date, string $plan = 'pro'): bool
    {
        $inRange = $date->gt(today()) && $date->lte(today()->addDays(config('rankedlaunch.paid_max_days_ahead')));

        if (! $inRange || $this->countOn($date, 'paid') >= config('rankedlaunch.paid_slots_per_day')) {
            return false;
        }

        return $plan !== 'featured' || $this->countOn($date, 'featured') < config('rankedlaunch.featured_slots_per_day');
    }

    /** @param 'free'|'paid'|'featured' $kind */
    private function countOn(Carbon $date, string $kind): int
    {
        return Product::whereIn('status', self::HOLDING)
            ->whereDate('launch_date', $date)
            ->when($kind === 'free', fn ($q) => $q->where('plan', 'free'))
            ->when($kind === 'paid', fn ($q) => $q->where('plan', '!=', 'free'))
            ->when($kind === 'featured', fn ($q) => $q->where('plan', 'featured'))
            ->count();
    }
}
