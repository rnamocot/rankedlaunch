<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    public const PRICING_TYPES = ['free' => 'Free', 'freemium' => 'Freemium', 'paid' => 'Paid'];

    protected $fillable = [
        'user_id', 'category_id', 'name', 'slug', 'url', 'tagline', 'description',
        'logo', 'screenshots', 'pricing_type', 'plan', 'status', 'launch_date',
        'requested_launch_date', 'requested_plan', 'is_dofollow', 'featured_until',
        'votes_count', 'rejection_reason', 'source', 'source_id', 'source_url', 'source_author',
    ];

    /** Where imported directory listings come from (source => label). */
    public const SOURCES = ['hn' => 'Show HN'];

    protected function casts(): array
    {
        return [
            'screenshots' => 'array',
            'launch_date' => 'date',
            'requested_launch_date' => 'date',
            'featured_until' => 'datetime',
            'is_dofollow' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function launchReminders(): HasMany
    {
        return $this->hasMany(LaunchReminder::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /* ---------- Scopes ---------- */

    public function scopeLive(Builder $query): Builder
    {
        return $query->where('status', 'approved')
            ->whereNotNull('launch_date')
            ->whereDate('launch_date', '<=', today());
    }

    /** Live products that launched here: excludes imported directory listings, which never rank. */
    public function scopeLaunched(Builder $query): Builder
    {
        return $query->live()->whereNull('source');
    }

    /** Approved launches scheduled after today. */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', 'approved')->whereDate('launch_date', '>', today());
    }

    public function scopeLaunchedBetween(Builder $query, Carbon $from, Carbon $to): Builder
    {
        return $query->launched()
            ->whereDate('launch_date', '>=', $from)
            ->whereDate('launch_date', '<=', $to);
    }

    public function scopeRanked(Builder $query): Builder
    {
        return $query->orderByDesc('votes_count')->orderBy('launch_date')->orderBy('id');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->live()->where('featured_until', '>', now());
    }

    /**
     * Pro launches for the homepage Featured row, rotated fairly:
     * launch-day Pro apps always show first; the remaining spots cycle through
     * the other featured apps every 10 minutes in a fixed order, so over a day
     * every Pro launch gets the same share of views (random picks can't promise that).
     */
    public static function featuredRotation(?int $count = null, ?Carbon $now = null): Collection
    {
        $count ??= config('rankedlaunch.featured_on_homepage', 3);
        $now ??= now();

        $pool = static::featured()->with('category')->orderByDesc('launch_date')->orderBy('id')->get();
        [$launchDay, $others] = $pool->partition(fn (Product $p) => $p->launch_date->isSameDay($now));

        $rotate = function ($items, int $slots) use ($now) {
            $items = $items->values();
            if ($items->count() <= $slots) {
                return $items;
            }
            $offset = intdiv($now->timestamp, 600) % $items->count();

            return $items->slice($offset)->concat($items->take($offset))->take($slots);
        };

        // Even launch-day apps rotate if there are more of them than spots.
        $first = $rotate($launchDay, $count);

        return $first->concat($rotate($others, $count - $first->count()))->values();
    }

    /** Total Pro launches currently in the Featured rotation. */
    public static function featuredPoolSize(): int
    {
        return static::featured()->count();
    }

    /* ---------- Helpers ---------- */

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'app';
        $slug = $base;
        $i = 2;
        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function logoUrl(): string
    {
        if ($this->logo && Str::startsWith($this->logo, ['http://', 'https://'])) {
            return $this->logo;
        }

        if ($this->logo) {
            return Storage::disk('public')->url($this->logo);
        }

        return 'https://www.google.com/s2/favicons?sz=128&domain='.urlencode($this->host());
    }

    public function screenshotUrls(): array
    {
        return collect($this->screenshots ?? [])
            ->map(fn ($path) => Str::startsWith($path, 'http') ? $path : Storage::disk('public')->url($path))
            ->all();
    }

    public function host(): string
    {
        return Str::of(parse_url($this->url, PHP_URL_HOST) ?? $this->url)->replaceStart('www.', '')->toString();
    }

    /** URL with ref param so makers can see traffic from us. */
    public function outboundUrl(): string
    {
        $sep = str_contains($this->url, '?') ? '&' : '?';

        return $this->url.$sep.'ref=rankedlaunch';
    }

    public function linkRel(): string
    {
        return $this->is_dofollow ? 'noopener' : 'nofollow ugc noopener';
    }

    public function isLive(): bool
    {
        return $this->status === 'approved' && $this->launch_date && $this->launch_date->lte(today());
    }

    /** An imported directory listing (e.g. from Show HN) rather than a launch by its maker. */
    public function isListing(): bool
    {
        return $this->source !== null;
    }

    /** Live launches compete in rankings and awards; listings don't. */
    public function isRankable(): bool
    {
        return $this->isLive() && ! $this->isListing();
    }

    public function sourceLabel(): ?string
    {
        return $this->source ? (self::SOURCES[$this->source] ?? ucfirst($this->source)) : null;
    }

    public function isFeatured(): bool
    {
        return $this->featured_until && $this->featured_until->isFuture();
    }

    /** Rank among products that launched the same day (1-based), or null if not live. */
    public function dailyRank(): ?int
    {
        if (! $this->isRankable()) {
            return null;
        }

        return static::launched()
            ->whereDate('launch_date', $this->launch_date)
            ->where(function ($q) {
                $q->where('votes_count', '>', $this->votes_count)
                    ->orWhere(fn ($q) => $q->where('votes_count', $this->votes_count)->where('id', '<', $this->id));
            })
            ->count() + 1;
    }

    /** Rank within the ISO week of its launch. */
    public function weeklyRank(): ?int
    {
        if (! $this->isRankable()) {
            return null;
        }

        $from = $this->launch_date->copy()->startOfWeek();
        $to = $this->launch_date->copy()->endOfWeek();

        return static::launchedBetween($from, $to)
            ->where(function ($q) {
                $q->where('votes_count', '>', $this->votes_count)
                    ->orWhere(fn ($q) => $q->where('votes_count', $this->votes_count)->where('id', '<', $this->id));
            })
            ->count() + 1;
    }

    /** Places that earn an award (badge, share image, email) in each period. */
    public const AWARD_TOP = 5;

    /** Rank among live products launched between $from and $to (1-based), or null if not live. */
    public function rankBetween(Carbon $from, Carbon $to): ?int
    {
        if (! $this->isRankable()) {
            return null;
        }

        return static::launchedBetween($from, $to)
            ->where(function ($q) {
                $q->where('votes_count', '>', $this->votes_count)
                    ->orWhere(fn ($q) => $q->where('votes_count', $this->votes_count)->where('id', '<', $this->id));
            })
            ->count() + 1;
    }

    /**
     * The best award this launch has earned, once its period is over:
     * Product of the Year > Month > Week > Day, top 5 in each.
     *
     * @return array{label: string, period: string, rank: int}|null
     */
    public function award(): ?array
    {
        if (! $this->isRankable()) {
            return null;
        }

        $d = $this->launch_date;
        $periods = [
            'Year' => [$d->copy()->startOfYear(), $d->copy()->endOfYear()],
            'Month' => [$d->copy()->startOfMonth(), $d->copy()->endOfMonth()],
            'Week' => [$d->copy()->startOfWeek(), $d->copy()->endOfWeek()],
            'Day' => [$d->copy()->startOfDay(), $d->copy()->endOfDay()],
        ];

        foreach ($periods as $period => [$from, $to]) {
            if ($to->isPast()) {
                $rank = $this->rankBetween($from, $to);
                if ($rank && $rank <= self::AWARD_TOP) {
                    return ['label' => "#{$rank} Product of the {$period}", 'period' => strtolower($period), 'rank' => $rank];
                }
            }
        }

        return null;
    }

    public function pricingLabel(): string
    {
        return self::PRICING_TYPES[$this->pricing_type] ?? ucfirst($this->pricing_type);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'awaiting_payment' => 'Awaiting payment',
            'pending' => 'In review',
            'approved' => $this->isLive() ? 'Live' : 'Scheduled',
            'rejected' => 'Not approved',
            default => ucfirst($this->status),
        };
    }
}
