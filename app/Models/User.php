<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password', 'avatar', 'provider', 'provider_id', 'timezone', 'username', 'bio', 'website', 'x_handle'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'banned_at' => 'datetime',
            'streak_last_date' => 'date',
        ];
    }

    /** Usernames that would clash with site pages or impersonate staff. */
    public const RESERVED_USERNAMES = [
        'admin', 'administrator', 'api', 'about', 'account', 'apps', 'auth', 'badge', 'blog', 'chat', 'dashboard',
        'discover', 'help', 'launch', 'login', 'logout', 'mod', 'moderator', 'pricing', 'rankedlaunch', 'rankings',
        'register', 'root', 'settings', 'staff', 'submit', 'support', 'system', 'terms', 'privacy', 'team',
    ];

    protected static function booted(): void
    {
        // Every account gets a profile handle (/@username) on creation.
        static::creating(function (User $user) {
            $user->username ??= static::uniqueUsername($user->name ?: 'maker');
        });
    }

    public static function uniqueUsername(string $name): string
    {
        $base = Str::of($name)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '')->limit(20, '')->toString();
        if (strlen($base) < 3 || in_array($base, self::RESERVED_USERNAMES, true)) {
            $base = 'maker'.$base;
        }
        $candidate = $base;
        $i = 2;
        while (static::where('username', $candidate)->exists()) {
            $candidate = $base.$i++;
        }

        return $candidate;
    }

    public function profileUrl(): string
    {
        return route('profile', $this->username);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
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

    /**
     * Count today towards the user's daily streak: consecutive UTC days with a visit.
     * Writes at most once per day.
     */
    public function recordActivity(): void
    {
        $today = today();

        if ($this->streak_last_date?->isSameDay($today)) {
            return;
        }

        $this->streak_days = $this->streak_last_date?->isSameDay($today->copy()->subDay()) ? $this->streak_days + 1 : 1;
        $this->longest_streak = max($this->longest_streak, $this->streak_days);
        $this->streak_last_date = $today;
        $this->saveQuietly();
    }

    /** The current streak, or 0 once a day has been missed. */
    public function currentStreak(): int
    {
        return $this->streak_last_date && $this->streak_last_date->gte(today()->subDay()) ? $this->streak_days : 0;
    }

    /**
     * A UTC moment in this user's time zone, e.g. "8:00 AM (Manila time)".
     * Falls back to UTC when we don't know their zone yet.
     */
    public function localTime(Carbon $utc, bool $withDay = false): string
    {
        if (! $this->timezone || ! in_array($this->timezone, timezone_identifiers_list(), true)) {
            return $utc->copy()->utc()->format($withDay ? 'l, M j \a\t H:i' : 'H:i').' UTC';
        }

        $local = $utc->copy()->setTimezone($this->timezone);
        $city = str_replace('_', ' ', Str::afterLast($this->timezone, '/'));

        return $local->format($withDay ? 'l, M j \a\t g:i A' : 'g:i A')." ({$city} time)";
    }

    /** Placeholder domain for X sign-ins that didn't share an email. Mail to it goes nowhere. */
    public const PLACEHOLDER_DOMAIN = '@users.rankedlaunch.com';

    /** The user's email if it can actually receive mail, otherwise null. */
    public function deliverableEmail(): ?string
    {
        return $this->email && ! str_ends_with($this->email, self::PLACEHOLDER_DOMAIN) ? $this->email : null;
    }

    public function initials(): string
    {
        return mb_strtoupper(mb_substr($this->name, 0, 1));
    }
}
