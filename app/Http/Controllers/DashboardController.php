<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\Vote;
use App\Services\LaunchScheduler;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $products = $user->products()->with('category')->latest()->get();
        $ids = $products->pluck('id');

        // Launches grouped by where they are in their life cycle.
        $groups = [
            'live' => $products->filter(fn (Product $p) => $p->isLive() && $p->launch_date->isToday()),
            'upcoming' => $products->filter(fn (Product $p) => in_array($p->status, ['pending', 'approved']) && $p->launch_date?->isFuture())
                ->sortBy('launch_date'),
            'action' => $products->filter(fn (Product $p) => in_array($p->status, ['draft', 'awaiting_payment', 'rejected'])),
            'past' => $products->filter(fn (Product $p) => $p->isLive() && $p->launch_date->lt(today()))
                ->sortByDesc('launch_date'),
        ];

        // 30-day series across all of this maker's products.
        $from = today()->subDays(29);
        $daily = DB::table('product_daily_stats')->whereIn('product_id', $ids)->where('date', '>=', $from->toDateString())
            ->selectRaw('date, SUM(views) as views, SUM(clicks) as clicks')->groupBy('date')->get()->keyBy(fn ($r) => substr($r->date, 0, 10));
        $votes = Vote::whereIn('product_id', $ids)->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as n')->groupBy('day')->pluck('n', 'day');

        $series = ['views' => [], 'clicks' => [], 'votes' => []];
        for ($i = 0; $i < 30; $i++) {
            $day = $from->copy()->addDays($i);
            $key = $day->toDateString();
            $series['views'][] = ['date' => $day, 'value' => (int) ($daily[$key]->views ?? 0)];
            $series['clicks'][] = ['date' => $day, 'value' => (int) ($daily[$key]->clicks ?? 0)];
            $series['votes'][] = ['date' => $day, 'value' => (int) ($votes[$key] ?? 0)];
        }

        // Last 7 days vs the 7 before, for the trend on each tile.
        $trend = function (array $points) {
            $values = array_column($points, 'value');
            $now = array_sum(array_slice($values, -7));
            $before = array_sum(array_slice($values, -14, 7));

            return ['week' => $now, 'change' => $before ? (int) round(($now - $before) / $before * 100) : null];
        };

        $launched = $products->filter->isLive();
        $ranks = $launched->map->dailyRank()->filter();

        return view('dashboard', [
            'products' => $products,
            'groups' => $groups,
            'hasProducts' => $products->isNotEmpty(),
            'kpis' => [
                ['Upvotes', $launched->sum('votes_count'), $trend($series['votes'])],
                ['Page views', $launched->sum('views_count'), $trend($series['views'])],
                ['Visits to your sites', $launched->sum('clicks_count'), $trend($series['clicks'])],
                ['Best daily rank', $ranks->isNotEmpty() ? '#'.$ranks->min() : '—', null],
            ],
            'series' => $series,
            'upvoted' => Vote::with('product.category')->where('user_id', $user->id)->latest()->limit(6)->get()
                ->pluck('product')->filter(),
            'justPaid' => $request->query('paid'),
        ]);
    }

    /** Makers can delete their own listing (page, votes, images). Paid launches aren't refunded after launch. */
    public function destroy(Request $request, Product $product)
    {
        $this->authorizeOwner($request, $product);
        $request->validate(['confirm' => ['accepted']], ['confirm.accepted' => 'Tick the box to confirm you want to delete this listing.']);

        $name = $product->name;
        if ($product->logo && ! str_starts_with($product->logo, 'http')) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($product->logo);
        }
        $local = array_filter($product->screenshots ?? [], fn ($s) => ! str_starts_with($s, 'http'));
        \Illuminate\Support\Facades\Storage::disk('public')->delete($local);

        ActivityLog::record('moderation', "{$request->user()->name} deleted their listing {$name}", user: $request->user());
        $product->delete();

        return redirect()->route('dashboard')->with('status', "{$name} was deleted.");
    }

    /** Launch hub: what happens next, launch-day kit, and results for one product. */
    public function show(Request $request, Product $product, LaunchScheduler $scheduler)
    {
        $this->authorizeOwner($request, $product);

        $product->load('category');

        return view('launch-hub', [
            'product' => $product,
            'dailyRank' => $product->dailyRank(),
            'weeklyRank' => $product->weeklyRank(),
            'daysUntilLaunch' => $product->launch_date ? (int) today()->diffInDays($product->launch_date, false) : null,
            'justPaid' => $request->boolean('paid'),
            'canReschedule' => $this->reschedulable($product),
        ]);
    }

    /** Pick a new launch date (before launch day only). */
    public function reschedule(Request $request, Product $product, LaunchScheduler $scheduler)
    {
        $this->authorizeOwner($request, $product);
        abort_unless($this->reschedulable($product), 403, 'This launch can no longer be moved. Launches can be rescheduled until the day before they go live.');

        return view('reschedule', [
            'product' => $product,
            'options' => $scheduler->rescheduleOptions($product),
        ]);
    }

    public function updateDate(Request $request, Product $product, LaunchScheduler $scheduler)
    {
        $this->authorizeOwner($request, $product);
        abort_unless($this->reschedulable($product), 403, 'This launch can no longer be moved.');

        $date = Carbon::parse($request->validate(['launch_date' => ['required', 'date']])['launch_date'])->startOfDay();

        if ($product->launch_date?->isSameDay($date)) {
            return redirect()->route('launch.hub', $product);
        }

        if (! $scheduler->canReschedule($product, $date)) {
            return back()->withErrors(['launch_date' => 'That date isn’t available for your plan. Pick another one.']);
        }

        $old = $product->launch_date;
        $product->launch_date = $date;
        // Pro's homepage feature window follows the launch.
        if ($product->plan === 'pro') {
            $product->featured_until = $date->copy()->addDays(config('rankedlaunch.plans.pro.featured_days', 7));
        }
        // Reminder emails should go out again for the new date.
        $product->notified_tomorrow_at = null;
        $product->notified_live_at = null;
        $product->save();

        ActivityLog::record('booking', "{$product->name} moved from {$old?->format('M j')} to {$date->format('M j')}", $product, $request->user());

        return redirect()->route('launch.hub', $product)->with('status', "Rescheduled. {$product->name} now launches {$date->format('l, M j')}.");
    }

    private function reschedulable(Product $product): bool
    {
        return in_array($product->status, ['pending', 'approved'])
            && $product->launch_date
            && $product->launch_date->gt(today());
    }

    private function authorizeOwner(Request $request, Product $product): void
    {
        abort_unless($request->user()->id === $product->user_id || $request->user()->is_admin, 403);
    }
}
