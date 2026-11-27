<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\Vote;
use App\Services\LaunchScheduler;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $products = $user->products()->with('category')->latest()->get();

        // Group launches by where they are in their life cycle.
        $groups = [
            'live' => $products->filter(fn (Product $p) => $p->isLive() && $p->launch_date->isToday()),
            'upcoming' => $products->filter(fn (Product $p) => in_array($p->status, ['pending', 'approved']) && $p->launch_date?->isFuture())
                ->sortBy('launch_date'),
            'action' => $products->filter(fn (Product $p) => in_array($p->status, ['draft', 'awaiting_payment', 'rejected'])),
            'past' => $products->filter(fn (Product $p) => $p->isLive() && $p->launch_date->lt(today()))
                ->sortByDesc('launch_date'),
        ];

        $launched = $products->filter->isLive();
        $ranks = $launched->map->dailyRank()->filter();

        return view('dashboard', [
            'groups' => $groups,
            'hasProducts' => $products->isNotEmpty(),
            'totals' => [
                'Upvotes' => $launched->sum('votes_count'),
                'Page views' => $launched->sum('views_count'),
                'Visits to your sites' => $launched->sum('clicks_count'),
                'Best daily rank' => $ranks->isNotEmpty() ? '#'.$ranks->min() : '—',
            ],
            'upvoted' => Vote::with('product.category')->where('user_id', $user->id)->latest()->limit(6)->get()
                ->pluck('product')->filter(),
            'justPaid' => $request->query('paid'),
        ]);
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
        if ($product->plan === 'featured') {
            $product->featured_until = $date->copy()->addDays(config('rankedlaunch.plans.featured.featured_days', 7));
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
