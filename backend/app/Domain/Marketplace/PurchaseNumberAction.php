<?php

namespace App\Domain\Marketplace;

use App\Domain\Wallet\WalletLedger;
use App\Jobs\ProvisionNumber;
use App\Models\NumberAssignment;
use App\Models\NumberOffer;
use App\Models\Order;
use App\Models\PhoneNumber;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use RuntimeException;

class PurchaseNumberAction
{
    public function __construct(
        private DatabaseManager $database,
        private WalletLedger $walletLedger,
    ) {
    }

    public function execute(
        int $userId,
        int $offerId,
        string $idempotencyKey,
        bool $autoRenew = false,
        ?int $customDurationDays = null,
    ): Order {
        $order = $this->database->transaction(function () use (
            $userId,
            $offerId,
            $idempotencyKey,
            $autoRenew,
            $customDurationDays,
        ) {
            $existing = Order::where('user_id', $userId)
                ->where('idempotency_key', $idempotencyKey)
                ->with(['phoneNumber.country', 'assignment'])
                ->first();

            if ($existing) {
                return $existing;
            }

            $offer = NumberOffer::query()
                ->with(['country', 'service', 'provider'])
                ->whereKey($offerId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (!$offer || !$offer->country || !$offer->country->is_active) {
                throw new RuntimeException('NUMBER_OFFER_UNAVAILABLE');
            }

            if ($offer->provider && $offer->provider->status !== 'active') {
                throw new RuntimeException('PROVIDER_UNAVAILABLE');
            }

            if ($autoRenew && !$offer->auto_renew_supported) {
                throw new RuntimeException('AUTO_RENEW_UNSUPPORTED');
            }

            $totalMinor = $offer->price_minor + $offer->setup_fee_minor;
            if ($totalMinor <= 0) {
                throw new RuntimeException('INVALID_OFFER_PRICE');
            }

            $phoneQuery = PhoneNumber::query()
                ->where('country_id', $offer->country_id)
                ->where('status', 'available')
                ->where(function ($query) {
                    $query->whereNull('reserved_until')
                        ->orWhere('reserved_until', '<=', now());
                })
                ->when($offer->provider_id, function ($query) use ($offer) {
                    $query->where('provider_id', $offer->provider_id);
                })
                ->whereHas('provider', function ($query) {
                    $query->where('status', 'active');
                });

            $phone = $phoneQuery->lockForUpdate()->first();
            if (!$phone) {
                throw new RuntimeException('NUMBER_UNAVAILABLE');
            }

            if ($offer->service_id && $offer->service) {
                $capabilities = $phone->capabilities ?? [];
                if (!in_array($offer->service->slug, $capabilities, true)) {
                    throw new RuntimeException('NUMBER_CAPABILITY_UNAVAILABLE');
                }
            }

            $durationDays = $this->durationDays($offer->term_type, $customDurationDays);
            $startsAt = now();
            $endsAt = $startsAt->copy()->addDays($durationDays);
            $orderNumber = 'VO-' . now()->format('ymd') . '-' . Str::upper(Str::random(10));
            $reservationToken = Str::uuid()->toString();

            $phone->forceFill([
                'status' => 'reserved',
                'reservation_token' => $reservationToken,
                'reserved_until' => now()->addMinutes(10),
            ])->save();

            $order = Order::create([
                'user_id' => $userId,
                'phone_number_id' => $phone->id,
                'provider_id' => $phone->provider_id,
                'order_number' => $orderNumber,
                'kind' => 'number_rental',
                'term_type' => $offer->term_type,
                'status' => 'pending_provisioning',
                'currency' => strtoupper($offer->currency),
                'subtotal_minor' => $offer->price_minor,
                'fee_minor' => $offer->setup_fee_minor,
                'total_minor' => $totalMinor,
                'idempotency_key' => $idempotencyKey,
                'placed_at' => now(),
                'metadata' => [
                    'offer_id' => $offer->id,
                    'service_id' => $offer->service_id,
                    'reservation_token' => $reservationToken,
                    'provisioning' => 'pending',
                ],
            ]);

            $this->walletLedger->debit(
                $userId,
                $totalMinor,
                strtoupper($offer->currency),
                'order:' . $idempotencyKey,
                'number_purchase',
                ['order_id' => $order->id, 'order_number' => $order->order_number],
            );

            NumberAssignment::create([
                'phone_number_id' => $phone->id,
                'user_id' => $userId,
                'order_id' => $order->id,
                'term_type' => $offer->term_type,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'auto_renew' => $autoRenew,
                'status' => 'pending_provisioning',
            ]);

            return $order;
        });

        // Provider I/O is deliberately outside the database transaction.
        ProvisionNumber::dispatch($order->id);

        return $order->fresh(['phoneNumber.country', 'assignment']);
    }

    private function durationDays(string $termType, ?int $customDurationDays): int
    {
        return match ($termType) {
            'instant', 'daily' => 1,
            'weekly' => 7,
            'monthly' => 30,
            'quarterly' => 90,
            'annual' => 365,
            'custom' => $this->customDuration($customDurationDays),
            default => throw new RuntimeException('INVALID_TERM_TYPE'),
        };
    }

    private function customDuration(?int $days): int
    {
        if ($days === null || $days < 1 || $days > 3650) {
            throw new RuntimeException('INVALID_CUSTOM_DURATION');
        }

        return $days;
    }
}
