<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhoneNumber extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_id',
        'provider_id',
        'number',
        'normalized_number',
        'type',
        'status',
        'capabilities',
        'provider_reference',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'capabilities' => 'array',
            'metadata' => 'array',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(NumberAssignment::class);
    }

    public function activeAssignment(): HasMany
    {
        return $this->hasMany(NumberAssignment::class)->where('status', 'active');
    }
}
