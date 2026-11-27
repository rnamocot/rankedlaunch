<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $filter = $request->query('filter');

        $users = User::withCount(['products', 'votes'])
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")))
            ->when($filter === 'admins', fn ($query) => $query->where('is_admin', true))
            ->when($filter === 'banned', fn ($query) => $query->whereNotNull('banned_at'))
            ->when($filter === 'makers', fn ($query) => $query->has('products'))
            ->latest()
            ->paginate(40)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'q', 'filter'));
    }

    public function show(User $user)
    {
        $user->loadCount(['products', 'votes']);

        return view('admin.users.show', [
            'user' => $user,
            'products' => $user->products()->latest()->get(),
            'payments' => $user->hasMany(\App\Models\Payment::class)->latest()->get(),
            'recentVotes' => Vote::with('product')->where('user_id', $user->id)->latest()->limit(20)->get(),
            'votesLastHour' => Vote::where('user_id', $user->id)->where('created_at', '>=', now()->subHour())->count(),
            'activity' => ActivityLog::where('user_id', $user->id)->latest('created_at')->limit(15)->get(),
        ]);
    }

    public function ban(Request $request, User $user)
    {
        abort_if($user->is($request->user()), 422, 'You can’t ban yourself.');

        if ($user->banned_at) {
            $user->forceFill(['banned_at' => null, 'ban_reason' => null])->save();
            ActivityLog::record('moderation', "Unbanned {$user->name}", user: $user);

            return back()->with('status', "{$user->name} unbanned.");
        }

        $user->forceFill(['banned_at' => now(), 'ban_reason' => $request->input('reason')])->save();

        $removed = $request->boolean('remove_votes') ? $this->deleteVotes($user) : 0;
        ActivityLog::record('moderation', "Banned {$user->name}".($removed ? " and removed {$removed} votes" : ''), user: $user, meta: ['reason' => $request->input('reason')]);

        return back()->with('status', "{$user->name} banned".($removed ? ", {$removed} votes removed" : '').'.');
    }

    public function removeVotes(User $user)
    {
        $removed = $this->deleteVotes($user);
        ActivityLog::record('moderation', "Removed {$removed} votes by {$user->name}", user: $user);

        return back()->with('status', "Removed {$removed} votes and updated rankings.");
    }

    public function toggleAdmin(Request $request, User $user)
    {
        abort_if($user->is($request->user()), 422, 'You can’t change your own admin access.');

        $user->forceFill(['is_admin' => ! $user->is_admin])->save();
        ActivityLog::record('moderation', ($user->is_admin ? 'Made ' : 'Removed admin from ').$user->name, user: $request->user());

        return back()->with('status', $user->is_admin ? "{$user->name} is now an admin." : "{$user->name} is no longer an admin.");
    }

    public function destroy(Request $request, User $user)
    {
        abort_if($user->is($request->user()), 422, 'You can’t delete yourself.');
        $request->validate(['confirm' => ['accepted']], ['confirm.accepted' => 'Tick the box to confirm deletion.']);

        $this->deleteVotes($user);
        $name = $user->name;
        $user->delete(); // products, votes and payments cascade

        ActivityLog::record('moderation', "Deleted user {$name} and their products", user: $request->user());

        return redirect()->route('admin.users.index')->with('status', "{$name} deleted.");
    }

    /** Delete a user's votes and fix the counts on the products they voted for. */
    private function deleteVotes(User $user): int
    {
        return DB::transaction(function () use ($user) {
            $productIds = Vote::where('user_id', $user->id)->pluck('product_id');
            $removed = Vote::where('user_id', $user->id)->delete();

            Product::whereIn('id', $productIds)->get()->each(
                fn (Product $p) => $p->update(['votes_count' => $p->votes()->count()])
            );

            return $removed;
        });
    }
}
