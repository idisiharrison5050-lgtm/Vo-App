<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderWebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'event_id',
        'event_type',
        'signature_valid',
        'status',
        'payload',
        'processed_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'signature_valid' => 'boolean',
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
