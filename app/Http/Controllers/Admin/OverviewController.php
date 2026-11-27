<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Subscriber;
use App\Models\User;
use App\Models\Vote;
use App\Services\Intelligence;
use App\Services\Pricing;
use Illuminate\Support\Facades\DB;

class OverviewController extends Controller
{
    public function __invoke(Pricing $pricing, Intelligence $intelligence)
    {
        $paid = Payment::where('status', 'paid');

        $kpis = [
            ['Revenue, all time', Pricing::dollars((int) (clone $paid)->sum('amount')), (clone $paid)->count().' paid launches'],
            ['Revenue, last 30 days', Pricing::dollars((int) (clone $paid)->where('created_at', '>=', now()->subDays(30))->sum('amount')), (clone $paid)->where('created_at', '>=', now()->subDays(30))->count().' sales'],
            ['Waiting for review', Product::where('status', 'pending')->count(), 'products', route('admin.products.index', ['status' => 'pending'])],
            ['Launch offer', $pricing->promoActive() ? $pricing->promoRemaining().' left' : 'Off', $pricing->promoActive() ? 'of '.$pricing->promo()['limit'].' spots' : 'regular prices', route('admin.settings')],
            ['New users, 7 days', User::where('created_at', '>=', now()->subDays(7))->count(), User::count().' total', route('admin.users.index')],
            ['Votes today', Vote::where('created_at', '>=', today())->count(), Vote::count().' all time', route('admin.votes')],
            ['Live today', Product::live()->whereDate('launch_date', today())->count(), 'launches'],
            ['Subscribers', Subscriber::whereNull('unsubscribed_at')->count(), 'newsletter', route('admin.subscribers.index')],
        ];

        $charts = [
            'Revenue per day' => [$this->daily(Payment::where('status', 'paid'), 'SUM(amount)'), 'money'],
            'Sign-ups per day' => [$this->daily(User::query(), 'COUNT(*)'), 'int'],
            'Votes per day' => [$this->daily(Vote::query(), 'COUNT(*)'), 'int'],
        ];

        // Next 14 days: how full each launch day is.
        $calendar = collect(range(0, 13))->map(function ($i) {
            $date = today()->addDays($i);
            $rows = Product::whereIn('status', ['pending', 'approved'])->whereDate('launch_date', $date)
                ->selectRaw("SUM(CASE WHEN plan = 'free' THEN 1 ELSE 0 END) as free_count, SUM(CASE WHEN plan != 'free' THEN 1 ELSE 0 END) as paid_count")
                ->first();

            return ['date' => $date, 'free' => (int) $rows->free_count, 'paid' => (int) $rows->paid_count];
        });

        return view('admin.overview', [
            'kpis' => $kpis,
            'charts' => $charts,
            'calendar' => $calendar,
            'pending' => Product::with('user')->where('status', 'pending')->orderByRaw("plan = 'free'")->orderBy('launch_date')->limit(6)->get(),
            'today' => Product::live()->whereDate('launch_date', today())->ranked()->limit(8)->get(),
            'activity' => ActivityLog::with('product')->latest('created_at')->limit(12)->get(),
            'flow' => $intelligence->flow(30),
            'insights' => $intelligence->insights(),
        ]);
    }

    /** Last 30 days, one value per day (missing days are 0). */
    private function daily($query, string $aggregate): array
    {
        $from = today()->subDays(29);

        $rows = $query->where('created_at', '>=', $from)
            ->selectRaw("DATE(created_at) as day, {$aggregate} as total")
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('total', 'day');

        return collect(range(0, 29))->map(function ($i) use ($from, $rows) {
            $day = $from->copy()->addDays($i);

            return ['date' => $day, 'value' => (int) ($rows[$day->toDateString()] ?? 0)];
        })->all();
    }
}
