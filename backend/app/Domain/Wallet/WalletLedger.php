<?php

namespace App\Domain\Wallet;

use App\Models\WalletAccount;
use App\Models\WalletLedgerEntry;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use RuntimeException;

class WalletLedger
{
    public function __construct(private DatabaseManager $database)
    {
    }

    public function credit(
        int $userId,
        int $amountMinor,
        string $currency,
        string $idempotencyKey,
        string $type,
        array $metadata = []
    ): WalletLedgerEntry {
        $this->assertAmount($amountMinor);

        return $this->database->transaction(function () use (
            $userId,
            $amountMinor,
            $currency,
            $idempotencyKey,
            $type,
            $metadata
        ) {
            $existing = WalletLedgerEntry::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }

            $wallet = WalletAccount::where('user_id', $userId)
                ->where('currency', $currency)
                ->lockForUpdate()
                ->first();

            if (!$wallet) {
                $wallet = WalletAccount::create([
                    'user_id' => $userId,
                    'currency' => $currency,
                    'available_minor' => 0,
                    'pending_minor' => 0,
                    'status' => 'active',
                ]);

                $wallet = WalletAccount::whereKey($wallet->id)->lockForUpdate()->firstOrFail();
            }

            if ($wallet->status !== 'active') {
                throw new RuntimeException('Wallet account is not active.');
            }

            $wallet->available_minor += $amountMinor;
            $wallet->save();

            return WalletLedgerEntry::create([
                'wallet_account_id' => $wallet->id,
                'user_id' => $userId,
                'reference' => 'WAL-' . Str::upper(Str::random(20)),
                'idempotency_key' => $idempotencyKey,
                'type' => $type,
                'direction' => 'credit',
                'amount_minor' => $amountMinor,
                'currency' => $currency,
                'balance_after_minor' => $wallet->available_minor,
                'status' => 'posted',
                'metadata' => $metadata,
            ]);
        });
    }

    public function debit(
        int $userId,
        int $amountMinor,
        string $currency,
        string $idempotencyKey,
        string $type,
        array $metadata = []
    ): WalletLedgerEntry {
        $this->assertAmount($amountMinor);

        return $this->database->transaction(function () use (
            $userId,
            $amountMinor,
            $currency,
            $idempotencyKey,
            $type,
            $metadata
        ) {
            $existing = WalletLedgerEntry::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }

            $wallet = WalletAccount::where('user_id', $userId)
                ->where('currency', $currency)
                ->lockForUpdate()
                ->first();

            if (!$wallet || $wallet->status !== 'active') {
                throw new RuntimeException('Wallet account is not active.');
            }

            if ($wallet->available_minor < $amountMinor) {
                throw new RuntimeException('Insufficient wallet funds.');
            }

            $wallet->available_minor -= $amountMinor;
            $wallet->save();

            return WalletLedgerEntry::create([
                'wallet_account_id' => $wallet->id,
                'user_id' => $userId,
                'reference' => 'WAL-' . Str::upper(Str::random(20)),
                'idempotency_key' => $idempotencyKey,
                'type' => $type,
                'direction' => 'debit',
                'amount_minor' => $amountMinor,
                'currency' => $currency,
                'balance_after_minor' => $wallet->available_minor,
                'status' => 'posted',
                'metadata' => $metadata,
            ]);
        });
    }

    private function assertAmount(int $amountMinor): void
    {
        if ($amountMinor <= 0) {
            throw new RuntimeException('Wallet amount must be greater than zero.');
        }
    }
}
