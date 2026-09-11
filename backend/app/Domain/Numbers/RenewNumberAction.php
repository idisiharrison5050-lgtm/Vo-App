<?php

namespace App\Domain\Numbers;

use App\Domain\Providers\ProviderManager;
use App\Domain\Wallet\WalletLedger;
use App\Models\NumberAssignment;
use App\Models\NumberOffer;
use App\Models\Order;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use RuntimeException;

class RenewNumberAction
{
    public function __construct(
        private DatabaseManager $database,
        private WalletLedger $walletLedger,
        private ProviderManager $providers,
    ) {
    }

    public function execute(int $userId, int $assignmentId, string $idempotencyKey): Order
    {
        return $this->database->transaction(function () use ($userId, $assignmentId, $idempotencyKey) {
            $existing = Order::where('user_id', $userId)
                ->where('idempotency_key', $idempotencyKey)
                ->with(['phoneNumber.country', 'assignment'])
                ->first();

            if ($existing) {
                return $existing;
            }

            $assignment = NumberAssignment::query()
                ->with(['phoneNumber.provider', 'phoneNumber.country'])
                ->whereKey($assignmentId)
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (!$assignment || !$assignment->phoneNumber || !$assignment->phoneNumber->provider) {
                throw new RuntimeException('NUMBER_ASSIGNMENT_UNAVAILABLE');
            }

            if (!$assignment->ends_at) {
                throw new RuntimeException('NUMBER_NOT_RENEWABLE');
            }

            $days = $this->durationDays($assignment->term_type);
            $offer = NumberOffer::query()
                ->where('country_id', $assignment->phoneNumber->country_id)
                ->where('provider_id', $assignment->phoneNumber->provider_id)
                ->where('term_type', $assignment->term_type)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (!$offer) {
                throw new RuntimeException('RENEWAL_OFFER_UNAVAILABLE');
            }

            $totalMinor = $offer->price_minor + $offer->setup_fee_minor;
            if ($totalMinor <= 0) {
                throw new RuntimeException('INVALID_RENEWAL_PRICE');
            }

            $providerReference = $assignment->phoneNumber->provider_reference;
            if (!$providerReference) {
                throw new RuntimeException('PROVIDER_REFERENCE_MISSING');
            }

            $order = Order::create([
                'user_id' => $userId,
                'phone_number_id' => $assignment->phone_number_id,
                'provider_id' => $assignment->phoneNumber->provider_id,
                'order_number' => 'VO-' . now()->format('ymd') . '-' . Str::upper(Str::random(10)),
                'kind' => 'number_renewal',
                'term_type' => $assignment->term_type,
                'status' => 'pending_provisioning',
                'currency' => strtoupper($offer->currency),
                'subtotal_minor' => $offer->price_minor,
                'fee_minor' => $offer->setup_fee_minor,
                'total_minor' => $totalMinor,
                'idempotency_key' => $idempotencyKey,
                'placed_at' => now(),
                'metadata' => ['assignment_id' => $assignment->id, 'duration_days' => $days],
            ]);

            $this->walletLedger->debit(
                $userId,
                $totalMinor,
                strtoupper($offer->currency),
                'order:' . $idempotencyKey,
                'number_renewal',
                ['order_id' => $order->id, 'assignment_id' => $assignment->id],
            );

            $result = $this->providers->driver($assignment->phoneNumber->provider)->renew($providerReference, $days);

            $newEnd = $assignment->ends_at->copy()->addDays($days);
            $assignment->forceFill(['ends_at' => $newEnd])->save();
            $order->forceFill([
                'status' => 'completed',
                'completed_at' => now(),
                'metadata' => array_merge($order->metadata ?? [], ['provider_reference' => $result['provider_reference'] ?? $providerReference]),
            ])->save();

            return $order->fresh(['phoneNumber.country', 'assignment']);
        });
    }

    private function durationDays(string $termType): int
    {
        return match ($termType) {
            'daily', 'instant' => 1,
            'weekly' => 7,
            'monthly' => 30,
            'quarterly' => 90,
            'annual' => 365,
            'custom' => 30,
            default => throw new RuntimeException('INVALID_TERM_TYPE'),
        };
    }
}
