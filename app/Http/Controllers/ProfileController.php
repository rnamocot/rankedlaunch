<?php

namespace App\Http\Controllers;

use App\Models\User;

/** Public maker profile at /@username: live launches, upvotes and awards. */
class ProfileController extends Controller
{
    public function show(string $username)
    {
        $user = User::where('username', strtolower($username))->whereNull('banned_at')->firstOrFail();

        $launches = $user->products()->live()->with('category')->latest('launch_date')->get();
        $awards = $launches->map(fn ($p) => ['product' => $p, 'award' => $p->award()])->filter(fn ($a) => $a['award'])->values();

        return view('profile', [
            'maker' => $user,
            'launches' => $launches,
            'awards' => $awards,
            'stats' => [
                'Launches' => $launches->count(),
                'Upvotes' => $launches->sum('votes_count'),
                'Awards' => $awards->count(),
            ],
        ]);
    }
}
