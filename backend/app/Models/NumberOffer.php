<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NumberOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_id',
        'service_id',
        'provider_id',
        'term_type',
        'price_minor',
        'setup_fee_minor',
        'currency',
        'billing_interval_days',
        'auto_renew_supported',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'price_minor' => 'integer',
            'setup_fee_minor' => 'integer',
            'billing_interval_days' => 'integer',
            'auto_renew_supported' => 'boolean',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
