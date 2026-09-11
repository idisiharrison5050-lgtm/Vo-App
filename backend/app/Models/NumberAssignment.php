<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NumberAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone_number_id',
        'user_id',
        'order_id',
        'term_type',
        'starts_at',
        'ends_at',
        'auto_renew',
        'status',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'released_at' => 'datetime',
            'auto_renew' => 'boolean',
        ];
    }

    public function phoneNumber(): BelongsTo
    {
        return $this->belongsTo(PhoneNumber::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isLongTerm(): bool
    {
        return in_array($this->term_type, ['monthly', 'quarterly', 'annual', 'custom'], true);
    }
}
