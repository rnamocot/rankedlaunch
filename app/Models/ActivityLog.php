<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Throwable;

class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    public const TYPES = [
        'signup' => 'Sign-ups',
        'submission' => 'Submissions',
        'booking' => 'Bookings',
        'payment' => 'Payments',
        'refund' => 'Refunds',
        'review' => 'Reviews',
        'email' => 'Emails',
        'moderation' => 'Moderation',
        'settings' => 'Settings',
        'system' => 'System',
    ];

    protected $fillable = ['type', 'message', 'user_id', 'product_id', 'meta'];

    protected function casts(): array
    {
        return ['meta' => 'array', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Record an event. Never throws: logging must not break the action it describes. */
    public static function record(string $type, string $message, ?Product $product = null, ?User $user = null, array $meta = []): void
    {
        try {
            static::create([
                'type' => $type,
                'message' => $message,
                'product_id' => $product?->id,
                'user_id' => $user?->id ?? $product?->user_id,
                'meta' => $meta ?: null,
            ]);
        } catch (Throwable $e) {
            Log::warning('Activity log failed: '.$e->getMessage());
        }
    }
}
