<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use App\Models\Vote;

/** Vote monitoring: spot vote rings and fake accounts before they distort rankings. */
class VoteController extends Controller
{
    public function __invoke()
    {
        // Accounts that voted a lot in the last 24 hours, newest accounts first.
        $fastVoters = User::query()
            ->whereNull('banned_at')
            ->whereHas('votes', fn ($q) => $q->where('created_at', '>=', now()->subDay()), '>=', 15)
            ->withCount(['votes as recent_votes' => fn ($q) => $q->where('created_at', '>=', now()->subDay())])
            ->orderByDesc('recent_votes')
            ->limit(20)
            ->get();

        // New accounts (under 24h old) that already voted.
        $newAccountVoters = User::query()
            ->whereNull('banned_at')
            ->where('created_at', '>=', now()->subDay())
            ->has('votes')
            ->withCount('votes')
            ->orderByDesc('votes_count')
            ->limit(20)
            ->get();

        // Today's launches: what share of their votes came from brand-new accounts.
        $products = Product::live()->whereDate('launch_date', '>=', today()->subDay())->ranked()->limit(20)->get()
            ->map(function (Product $p) {
                // A vote is "fresh" when the account was under a day old when it voted.
                $votes = $p->votes()->with('user:id,created_at')->get(['user_id', 'created_at']);
                $total = $votes->count();
                $fresh = $votes->filter(fn ($v) => $v->user && $v->user->created_at->gt($v->created_at->copy()->subDay()))->count();

                return ['product' => $p, 'total' => $total, 'fresh' => $fresh, 'share' => $total ? round($fresh / $total * 100) : 0];
            });

        return view('admin.votes', [
            'fastVoters' => $fastVoters,
            'newAccountVoters' => $newAccountVoters,
            'products' => $products,
            'recent' => Vote::with('user', 'product')->latest()->limit(40)->get(),
        ]);
    }
}
