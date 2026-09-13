<?php

namespace App\Domain\Numbers;

use App\Domain\Wallet\WalletLedger;
use App\Jobs\RenewNumber;
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
    ) {
    }

    public function execute(int $userId, int $assignmentId, string $idempotencyKey): Order
    {
        $order = $this->database->transaction(function () use ($userId, $assignmentId, $idempotencyKey) {
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

            if (!$assignment->phoneNumber->provider_reference) {
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
                'metadata' => [
                    'assignment_id' => $assignment->id,
                    'term_type' => $assignment->term_type,
                    'duration_days' => $days,
                    'provider_reference' => $assignment->phoneNumber->provider_reference,
                    'provider_operation_reference' => 'renewal:' . $idempotencyKey,
                ],
            ]);

            $this->walletLedger->debit(
                $userId,
                $totalMinor,
                strtoupper($offer->currency),
                'order:' . $idempotencyKey,
                'number_renewal',
                ['order_id' => $order->id, 'assignment_id' => $assignment->id],
            );

            return $order;
        });

        if ($order->status === 'pending_provisioning') {
            RenewNumber::dispatch($order->id);
        }

        return $order->fresh(['phoneNumber.country', 'assignment']);
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
