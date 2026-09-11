<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone_number_id',
        'number_assignment_id',
        'user_id',
        'provider_message_id',
        'direction',
        'from_number',
        'to_number',
        'body',
        'received_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function phoneNumber(): BelongsTo
    {
        return $this->belongsTo(PhoneNumber::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(NumberAssignment::class, 'number_assignment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
