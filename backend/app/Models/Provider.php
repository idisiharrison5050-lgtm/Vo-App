<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provider extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'status',
        'capabilities',
        'metadata',
        'last_health_check_at',
    ];

    protected function casts(): array
    {
        return [
            'capabilities' => 'array',
            'metadata' => 'array',
            'last_health_check_at' => 'datetime',
        ];
    }

    public function phoneNumbers(): HasMany
    {
        return $this->hasMany(PhoneNumber::class);
    }

    public function webhookEvents(): HasMany
    {
        return $this->hasMany(ProviderWebhookEvent::class);
    }
}
