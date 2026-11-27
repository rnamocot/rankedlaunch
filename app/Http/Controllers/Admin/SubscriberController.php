<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use Illuminate\Http\Request;

class SubscriberController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));

        $subscribers = Subscriber::when($q !== '', fn ($query) => $query->where('email', 'like', "%{$q}%"))
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('admin.subscribers', [
            'subscribers' => $subscribers,
            'q' => $q,
            'active' => Subscriber::whereNull('unsubscribed_at')->count(),
            'week' => Subscriber::where('created_at', '>=', now()->subDays(7))->count(),
        ]);
    }

    public function toggle(Subscriber $subscriber)
    {
        $subscriber->update(['unsubscribed_at' => $subscriber->unsubscribed_at ? null : now()]);

        return back()->with('status', $subscriber->unsubscribed_at ? 'Unsubscribed.' : 'Resubscribed.');
    }

    public function export()
    {
        $rows = Subscriber::whereNull('unsubscribed_at')->orderBy('id')->get(['email', 'source', 'created_at']);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['email', 'source', 'subscribed_at']);
            foreach ($rows as $row) {
                fputcsv($out, [$row->email, $row->source, $row->created_at->toDateTimeString()]);
            }
            fclose($out);
        }, 'rankedlaunch-subscribers.csv', ['Content-Type' => 'text/csv']);
    }
}
