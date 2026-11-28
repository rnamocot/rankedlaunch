<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** In-app notifications: replies, comments and upvotes on your launches, awards, launch reminders. */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $notifications = $user->notifications()->paginate(30);

        // Showing the list counts as reading it.
        $user->unreadNotifications()->update(['read_at' => now()]);

        return view('notifications', ['notifications' => $notifications]);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }
}
