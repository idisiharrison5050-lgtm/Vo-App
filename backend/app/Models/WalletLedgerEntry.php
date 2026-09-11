<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletLedgerEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_account_id',
        'user_id',
        'reference',
        'idempotency_key',
        'type',
        'direction',
        'amount_minor',
        'currency',
        'balance_after_minor',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'balance_after_minor' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function walletAccount(): BelongsTo
    {
        return $this->belongsTo(WalletAccount::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
