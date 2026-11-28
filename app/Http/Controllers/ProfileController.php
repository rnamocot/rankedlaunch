<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

/** Public maker profile at /@username: live launches, upvotes and awards. */
class ProfileController extends Controller
{
    public function show(Request $request, string $username)
    {
        $user = User::where('username', strtolower($username))->whereNull('banned_at')->firstOrFail();

        $launches = $user->products()->live()->with('category')->latest('launch_date')->get();
        $awards = $launches->map(fn ($p) => ['product' => $p, 'award' => $p->award()])->filter(fn ($a) => $a['award'])->values();

        // Saved products are private: only the owner sees them on their own profile.
        $saved = $request->user()?->is($user)
            ? Product::live()->with('category')
                ->whereIn('id', $user->bookmarks()->select('product_id'))
                ->ranked()->get()
            : collect();

        return view('profile', [
            'saved' => $saved,
            'maker' => $user,
            'launches' => $launches,
            'awards' => $awards,
            'stats' => [
                'Launches' => $launches->count(),
                'Upvotes' => $launches->sum('votes_count'),
                'Awards' => $awards->count(),
                'Day streak' => $user->currentStreak(),
            ],
        ]);
    }
}
