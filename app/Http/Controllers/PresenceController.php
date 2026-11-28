<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Comment;
use App\Models\LaunchReminder;
use App\Models\Product;
use App\Models\Vote;
use App\Services\VisitorTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/** Live visitor counts and the homepage "Happening now" stream. */
class PresenceController extends Controller
{
    public function ping(Request $request, VisitorTracker $tracker): JsonResponse
    {
        $visitorId = $tracker->visitorId($request);

        return response()->json($tracker->ping($request, $visitorId))
            ->header('Cache-Control', 'no-store')
            ->withCookie(cookie(VisitorTracker::COOKIE, $visitorId, 60 * 24 * 365, httpOnly: true, sameSite: 'lax'));
    }

    public function activity(): JsonResponse
    {
        return response()->json(['events' => Cache::remember('live-activity', 10, fn () => self::events())])
            ->header('Cache-Control', 'no-store');
    }

    /**
     * Recent real activity, newest first. Upvotes, saves and reminders never say who.
     *
     * @return array<int, array{id: string, type: string, text: string, product: string, url: ?string, logo: string, at: string}>
     */
    public static function events(int $limit = 24): array
    {
        $since = now()->subDays(14);
        $event = fn (string $type, Product $p, string $text, Carbon $at, bool $link = true) => [
            'id' => $type.'-'.$p->id.'-'.$at->timestamp,
            'type' => $type,
            'text' => $text,
            'product' => $p->name,
            'url' => $link ? route('product', $p) : null,
            'logo' => $p->logoUrl(),
            'at' => $at->toIso8601String(),
        ];

        /** @var Collection<int, array> $events */
        $events = collect()
            ->concat(Vote::with('product')->where('created_at', '>=', $since)->latest()->limit($limit)->get()
                ->filter(fn ($v) => $v->product?->isLive())
                ->map(fn ($v) => $event('vote', $v->product, 'got an upvote', $v->created_at)))
            ->concat(Comment::with('product')->where('created_at', '>=', $since)->latest()->limit($limit)->get()
                ->filter(fn ($c) => $c->product?->isLive())
                ->map(fn ($c) => $event('comment', $c->product, 'got a new comment', $c->created_at)))
            ->concat(Product::launched()->whereDate('launch_date', '>=', $since)->latest('launch_date')->limit($limit)->get()
                ->map(fn ($p) => $event('launch', $p, 'went live', $p->launch_date->copy()->startOfDay())))
            ->concat(Product::live()->whereNotNull('source')->where('created_at', '>=', $since)->latest()->limit($limit)->get()
                ->map(fn ($p) => $event('listed', $p, 'was listed from '.$p->sourceLabel(), $p->created_at)))
            ->concat(Product::upcoming()->where('updated_at', '>=', $since)->latest('updated_at')->limit($limit)->get()
                ->map(fn ($p) => $event('scheduled', $p, 'booked a launch for '.$p->launch_date->format('M j'), $p->updated_at, link: false)))
            ->concat(LaunchReminder::with('product')->where('created_at', '>=', $since)->latest()->limit($limit)->get()
                ->filter(fn ($r) => $r->product)
                ->map(fn ($r) => $event('waiting', $r->product, 'has someone waiting for launch', $r->created_at, link: $r->product->isLive())))
            ->concat(Bookmark::with('product')->where('created_at', '>=', $since)->latest()->limit($limit)->get()
                ->filter(fn ($b) => $b->product?->isLive())
                ->map(fn ($b) => $event('save', $b->product, 'was saved', $b->created_at)));

        return $events->filter(fn ($e) => Carbon::parse($e['at'])->lte(now()))
            ->sortByDesc('at')->unique('id')->take($limit)->values()->all();
    }
}
