<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'avatar', 'provider', 'provider_id', 'timezone'])]
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
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    /**
     * A UTC moment in this user's time zone, e.g. "8:00 AM (Manila time)".
     * Falls back to UTC when we don't know their zone yet.
     */
    public function localTime(\Illuminate\Support\Carbon $utc, bool $withDay = false): string
    {
        if (! $this->timezone || ! in_array($this->timezone, timezone_identifiers_list(), true)) {
            return $utc->copy()->utc()->format($withDay ? 'l, M j \a\t H:i' : 'H:i').' UTC';
        }

        $local = $utc->copy()->setTimezone($this->timezone);
        $city = str_replace('_', ' ', \Illuminate\Support\Str::afterLast($this->timezone, '/'));

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
