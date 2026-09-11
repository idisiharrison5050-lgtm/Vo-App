<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'phone_number_id',
        'provider_id',
        'order_number',
        'kind',
        'term_type',
        'status',
        'currency',
        'subtotal_minor',
        'fee_minor',
        'total_minor',
        'idempotency_key',
        'placed_at',
        'completed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_minor' => 'integer',
            'fee_minor' => 'integer',
            'total_minor' => 'integer',
            'placed_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function phoneNumber(): BelongsTo
    {
        return $this->belongsTo(PhoneNumber::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function assignment(): HasOne
    {
        return $this->hasOne(NumberAssignment::class);
    }
}
