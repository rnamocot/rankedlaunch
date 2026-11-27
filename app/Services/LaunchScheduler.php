<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Carbon;

/**
 * Launch calendar. Every plan can launch today ("list it now") or on any future day;
 * the only limits are the daily caps, so the board never gets flooded:
 *  - free_slots_per_day:     free launches per day
 *  - paid_slots_per_day:     Pro launches per day
 */
class LaunchScheduler
{
    /** Statuses that hold a slot in the calendar. */
    private const HOLDING = ['pending', 'approved'];

    /**
     * Each day from today, with how many spots are left for $plan.
     * $exclude: a product moving dates doesn't count against itself.
     *
     * @return array<int, array{date: Carbon, left: int, current: bool}>
     */
    public function dateOptions(string $plan, ?Product $exclude = null, ?int $days = null): array
    {
        $days ??= config('rankedlaunch.max_days_ahead', 90);
        $end = today()->addDays($days);

        $rows = Product::whereIn('status', self::HOLDING)
            ->when($exclude, fn ($q) => $q->whereKeyNot($exclude->id))
            ->whereDate('launch_date', '>=', today())
            ->whereDate('launch_date', '<=', $end)
            ->selectRaw("DATE(launch_date) as day,
                SUM(CASE WHEN plan = 'free' THEN 1 ELSE 0 END) as free,
                SUM(CASE WHEN plan != 'free' THEN 1 ELSE 0 END) as paid,
                SUM(CASE WHEN plan = 'featured' THEN 1 ELSE 0 END) as pro")
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $options = [];
        for ($i = 0; $i <= $days; $i++) {
            $date = today()->addDays($i);
            $row = $rows[$date->toDateString()] ?? null;
            $options[] = [
                'date' => $date,
                'left' => $this->leftFor($plan, $row),
                'current' => (bool) $exclude?->launch_date?->isSameDay($date),
            ];
        }

        return $options;
    }

    public function available(Carbon $date, string $plan, ?Product $exclude = null): bool
    {
        if ($date->lt(today()) || $date->gt(today()->addDays(config('rankedlaunch.max_days_ahead', 90)))) {
            return false;
        }

        $option = collect($this->dateOptions($plan, $exclude, (int) today()->diffInDays($date)))->last();

        return $option['left'] > 0;
    }

    /** Dates a booked (not yet live) product can move to. */
    public function rescheduleOptions(Product $product): array
    {
        return $this->dateOptions($product->plan, $product);
    }

    public function canReschedule(Product $product, Carbon $date): bool
    {
        return $product->launch_date?->isSameDay($date) || $this->available($date, $product->plan, $product);
    }

    /** Paid spots per day for the next $days days (used by admin insights). */
    public function paidDates(int $days = 14): array
    {
        return array_slice($this->dateOptions('pro', null, $days), 1);
    }

    private function leftFor(string $plan, $row): int
    {
        $free = (int) ($row->free ?? 0);
        $paid = (int) ($row->paid ?? 0);
        $pro = (int) ($row->pro ?? 0);

        $left = match ($plan) {
            'free' => config('rankedlaunch.free_slots_per_day') - $free,
            default => config('rankedlaunch.paid_slots_per_day') - $paid,
        };

        return max(0, $left);
    }
}
