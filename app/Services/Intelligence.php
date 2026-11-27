<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Support\Facades\Cache;

/**
 * Admin intelligence: the launch pipeline as a flow with conversion rates,
 * plus rule-based insights that each come with a suggested action.
 */
class Intelligence
{
    public function __construct(private Pricing $pricing, private LaunchScheduler $scheduler) {}

    /** Pipeline over the last $days days. Each stage: label, count, help. */
    public function flow(int $days = 30): array
    {
        $since = now()->subDays($days);
        $products = Product::where('created_at', '>=', $since);

        $signups = User::where('created_at', '>=', $since)->count();
        $submitted = (clone $products)->count();
        $booked = (clone $products)->whereIn('status', ['pending', 'approved', 'rejected'])->count();
        $paid = (clone $products)->where('plan', '!=', 'free')->whereIn('status', ['pending', 'approved'])->count();
        $approved = (clone $products)->where('status', 'approved')->count();
        $liveProducts = (clone $products)->live()->get();
        $top3 = $liveProducts->filter(fn (Product $p) => $p->launch_date->lt(today()) && $p->dailyRank() <= Product::AWARD_TOP)->count();

        return [
            'days' => $days,
            'stages' => [
                ['Sign-ups', $signups, 'New accounts'],
                ['Submitted', $submitted, 'Filled in the product form'],
                ['Booked', $booked, 'Picked a plan and date'],
                ['Approved', $approved, 'Passed review'],
                ['Live', $liveProducts->count(), 'Launch day reached'],
                ['Top 5', $top3, 'Finished top 5 of the day'],
            ],
            'split' => ['free' => max(0, $booked - $paid), 'paid' => $paid],
        ];
    }

    /**
     * Insights, most urgent first. Each: [level, title, detail, action label, action url].
     * Levels: action (do something now), watch (keep an eye on it), good (working well).
     */
    public function insights(): array
    {
        $out = [];
        $add = function (string $level, string $title, string $detail, ?string $label = null, ?string $url = null) use (&$out) {
            $out[] = compact('level', 'title', 'detail', 'label', 'url');
        };

        // --- System health -------------------------------------------------
        $lastRun = Cache::get('rankedlaunch.scheduler_last_run');
        if (! $lastRun || now()->diffInHours($lastRun) >= 2) {
            $add('action', 'Scheduled emails aren’t running',
                $lastRun ? 'Last run '.now()->parse($lastRun)->diffForHumans().'. Launch reminders and results won’t go out.' : 'The scheduler has never run. Launch reminders and results won’t go out until the cron job is set up.',
                'Check system', route('admin.system'));
        }

        $gateway = app(Payments::class)->gateway();
        if (! $gateway->configured() && app()->isProduction()) {
            $add('action', 'Payments aren’t set up', 'Nobody can buy a paid launch. Add your '.ucfirst($gateway->name()).' keys to .env.', 'Check system', route('admin.system'));
        }

        if (config('mail.default') === 'log' && app()->isProduction()) {
            $add('action', 'Emails aren’t being delivered', 'Mail is set to “log”, so makers get no emails. Add your email provider to .env.', 'Check system', route('admin.system'));
        }

        $failedEmails = ActivityLog::where('type', 'email')->where('created_at', '>=', now()->subDay())->where('message', 'like', 'FAILED%')->count();
        if ($failedEmails) {
            $add('action', "{$failedEmails} ".str('email')->plural($failedEmails).' failed in the last 24 hours', 'Makers may have missed reminders. Check your mail settings.', 'See activity', route('admin.activity', ['type' => 'email']));
        }

        // --- Review queue ----------------------------------------------------
        $paidWaiting = Product::where('status', 'pending')->where('plan', '!=', 'free')->where('updated_at', '<=', now()->subHours(12))->count();
        if ($paidWaiting) {
            $add('action', "{$paidWaiting} paid ".str('launch')->plural($paidWaiting).' waiting over 12 hours', 'Paying makers expect a fast review. The site promises about 24 hours.', 'Review now', route('admin.products.index', ['status' => 'pending']));
        }

        $soon = Product::where('status', 'pending')->whereNotNull('launch_date')->whereDate('launch_date', '<=', today()->addDays(2))->count();
        if ($soon) {
            $add('action', "{$soon} unreviewed ".str('product')->plural($soon).' launch within 2 days', 'If they aren’t approved in time, they miss their launch day.', 'Review now', route('admin.products.index', ['status' => 'pending']));
        }

        // --- Vote integrity --------------------------------------------------
        $suspects = User::whereNull('banned_at')
            ->whereHas('votes', fn ($q) => $q->where('created_at', '>=', now()->subDay()), '>=', 15)
            ->count();
        if ($suspects) {
            $add('action', "{$suspects} ".str('account')->plural($suspects).' voting unusually fast', '15+ votes in a day can mean a vote ring or a bot.', 'Check votes', route('admin.votes'));
        }

        Product::live()->whereDate('launch_date', '>=', today()->subDay())->where('votes_count', '>=', 10)->get()
            ->each(function (Product $p) use ($add) {
                $votes = $p->votes()->with('user:id,created_at')->get(['user_id', 'created_at']);
                $fresh = $votes->filter(fn ($v) => $v->user && $v->user->created_at->gt($v->created_at->copy()->subDay()))->count();
                $share = $votes->count() ? $fresh / $votes->count() : 0;
                if ($share >= 0.4) {
                    $add('action', "Suspicious votes on {$p->name}", round($share * 100).'% of its votes come from accounts created the same day.', 'Check votes', route('admin.votes'));
                }
            });

        // --- Revenue and pricing --------------------------------------------
        if ($this->pricing->promo()['enabled']) {
            $limit = $this->pricing->promo()['limit'];
            $left = $this->pricing->promoRemaining();
            $sold = $limit - $left;
            if ($left === 0) {
                $add('watch', 'Launch offer sold out', "All {$limit} spots are gone and Pro is back at full price. Announce it: “sold out” posts drive sales at the new price.", 'Settings', route('admin.settings'));
            } elseif ($limit > 0 && $sold / $limit >= 0.8) {
                $add('watch', "Launch offer is {$sold}/{$limit} sold", "Only {$left} left. Post about it now: scarcity converts best in the last few spots.", 'Settings', route('admin.settings'));
            }
        }

        $thisWeek = (int) Payment::where('status', 'paid')->where('created_at', '>=', now()->subDays(7))->sum('amount');
        $lastWeek = (int) Payment::where('status', 'paid')->whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])->sum('amount');
        if ($thisWeek && $lastWeek) {
            $change = round(($thisWeek - $lastWeek) / $lastWeek * 100);
            $add($change >= 0 ? 'good' : 'watch', 'Revenue '.($change >= 0 ? 'up' : 'down').' '.abs($change).'% this week',
                Pricing::dollars($thisWeek).' in the last 7 days vs '.Pricing::dollars($lastWeek).' the week before.');
        }

        $submitted = Product::where('created_at', '>=', now()->subDays(30))->count();
        $paid = Product::where('created_at', '>=', now()->subDays(30))->where('plan', '!=', 'free')->whereIn('status', ['pending', 'approved'])->count();
        if ($submitted >= 20) {
            $rate = round($paid / $submitted * 100);
            $rate < 10
                ? $add('watch', "Only {$rate}% of submissions pay", 'Most makers choose free. Try a stronger launch offer, or show the backlink’s SEO value more clearly.', 'Settings', route('admin.settings'))
                : $add('good', "{$rate}% of submissions pay", 'Healthy paid conversion over the last 30 days.');
        }

        // --- Queue capacity ---------------------------------------------------

        $fullDays = collect($this->scheduler->paidDates(7))->filter(fn ($d) => $d['left'] === 0)->count();
        if ($fullDays >= 4) {
            $add('watch', "Paid launches are full on {$fullDays} of the next 7 days", 'Demand is high. You could raise the Pro price or add paid slots.', 'Settings', route('admin.settings'));
        }

        // --- Content ---------------------------------------------------------
        if (! Product::live()->whereDate('launch_date', today())->exists()) {
            $add('watch', 'No launches today', 'An empty board looks quiet to visitors. Feature a strong product, or move a scheduled launch to today.', 'Products', route('admin.products.index', ['status' => 'scheduled']));
        }

        if (empty($out)) {
            $add('good', 'All clear', 'Nothing needs your attention right now.');
        }

        $order = ['action' => 0, 'watch' => 1, 'good' => 2];
        usort($out, fn ($a, $b) => $order[$a['level']] <=> $order[$b['level']]);

        return $out;
    }
}
