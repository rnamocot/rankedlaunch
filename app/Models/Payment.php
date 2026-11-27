<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'user_id', 'product_id', 'provider', 'provider_order_id',
        'plan', 'amount', 'is_promo', 'currency', 'status', 'payload',
    ];

    protected function casts(): array
    {
        return ['payload' => 'array', 'is_promo' => 'boolean'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
