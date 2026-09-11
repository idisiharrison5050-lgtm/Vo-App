<?php

namespace App\Domain\Payments;

use App\Models\PaymentIntent;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use RuntimeException;

class PaymentIntentAction
{
    public function __construct(private DatabaseManager $database, private PaymentManager $payments)
    {
    }

    public function create(int $userId, int $amountMinor, string $currency, string $provider, string $idempotencyKey): PaymentIntent
    {
        if ($amountMinor <= 0) {
            throw new RuntimeException('INVALID_PAYMENT_AMOUNT');
        }

        $currency = strtoupper($currency);

        $intent = $this->database->transaction(function () use ($userId, $amountMinor, $currency, $provider, $idempotencyKey) {
            $existing = PaymentIntent::where('user_id', $userId)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                return $existing;
            }

            return PaymentIntent::create([
                'user_id' => $userId,
                'reference' => 'PAY-' . Str::upper(Str::random(20)),
                'provider' => $provider,
                'currency' => $currency,
                'amount_minor' => $amountMinor,
                'status' => 'pending',
                'idempotency_key' => $idempotencyKey,
                'expires_at' => now()->addMinutes(30),
            ]);
        });

        if ($intent->provider_reference || $intent->status !== 'pending') {
            return $intent->fresh();
        }

        try {
            $result = $this->payments->driver($provider)->createIntent([
                'reference' => $intent->reference,
                'amount_minor' => $amountMinor,
                'currency' => $currency,
                'user_id' => $userId,
            ]);
        } catch (PaymentException $exception) {
            $intent->forceFill([
                'status' => 'failed',
                'metadata' => array_merge($intent->metadata ?? [], ['provider_error' => $exception->getMessage()]),
            ])->save();
            throw $exception;
        }

        $intent->forceFill([
            'provider_reference' => $result['provider_reference'] ?? null,
            'status' => 'ready',
            'metadata' => $result['metadata'] ?? [],
        ])->save();

        return $intent->fresh();
    }
}
