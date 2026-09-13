<?php

namespace App\Jobs;

use App\Domain\Providers\ProviderException;
use App\Domain\Providers\ProviderManager;
use App\Domain\Wallet\WalletLedger;
use App\Models\NumberAssignment;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\InteractsWithQueue;
use Illuminate\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

class RenewNumber implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public function __construct(public int $orderId)
    {
    }

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(ProviderManager $providers, WalletLedger $walletLedger): void
    {
        $order = Order::with(['phoneNumber.provider', 'assignment'])->find($this->orderId);
        if (!$order || $order->status !== 'pending_provisioning') {
            return;
        }

        $assignment = $order->assignment;
        $phone = $order->phoneNumber;
        $provider = $phone ? $phone->provider : null;
        $reference = $phone ? $phone->provider_reference : null;
        $days = (int) ($order->metadata['duration_days'] ?? 0);
        $operationReference = $order->metadata['provider_operation_reference'] ?? ('renewal:order:' . $order->id);

        if (!$assignment || !$phone || !$provider || !$reference || $days < 1) {
            $this->failRenewal($order, $walletLedger, 'Renewal state is incomplete.');
            return;
        }

        try {
            // External provider mutation deliberately happens outside a DB transaction.
            // The operation reference lets real providers deduplicate a retried mutation.
            $result = $providers->driver($provider)->renew($reference, $days, $operationReference);
        } catch (ProviderException $exception) {
            if ($exception->retryable) {
                throw $exception;
            }

            $this->failRenewal($order, $walletLedger, $exception->getMessage());
            return;
        }

        DB::transaction(function () use ($order, $assignment, $phone, $result) {
            $lockedOrder = Order::query()->lockForUpdate()->find($order->id);
            if (!$lockedOrder || $lockedOrder->status !== 'pending_provisioning') {
                return;
            }

            $lockedAssignment = NumberAssignment::query()->lockForUpdate()->find($assignment->id);
            if (!$lockedAssignment || $lockedAssignment->status !== 'active') {
                throw new \RuntimeException('Renewal assignment is no longer active.');
            }

            $lockedPhone = $phone->newQuery()->lockForUpdate()->find($phone->id);
            if (!$lockedPhone) {
                throw new \RuntimeException('Renewal number no longer exists.');
            }

            $termType = (string) ($lockedOrder->metadata['term_type'] ?? $lockedOrder->term_type);
            $lockedAssignment->forceFill([
                'ends_at' => $this->nextEndDate($lockedAssignment->ends_at, $termType),
            ])->save();

            if (!empty($result['provider_reference']) && $result['provider_reference'] !== $lockedPhone->provider_reference) {
                $lockedPhone->forceFill(['provider_reference' => $result['provider_reference']])->save();
            }

            $lockedOrder->forceFill([
                'status' => 'completed',
                'completed_at' => now(),
                'metadata' => array_merge($lockedOrder->metadata ?? [], [
                    'provider_reference' => $result['provider_reference'] ?? $lockedPhone->provider_reference,
                    'provider_renewed_at' => now()->toISOString(),
                ]),
            ])->save();
        });
    }

    private function nextEndDate($currentEnd, string $termType)
    {
        if (!$currentEnd) {
            return now()->addDays($this->durationDays($termType));
        }

        return match ($termType) {
            'daily', 'instant' => $currentEnd->copy()->addDay(),
            'weekly' => $currentEnd->copy()->addWeek(),
            'monthly' => $currentEnd->copy()->addMonthNoOverflow(),
            'quarterly' => $currentEnd->copy()->addMonthsNoOverflow(3),
            'annual' => $currentEnd->copy()->addYearNoOverflow(),
            'custom' => $currentEnd->copy()->addDays($this->durationDays($termType)),
            default => throw new \RuntimeException('INVALID_TERM_TYPE'),
        };
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
            default => throw new \RuntimeException('INVALID_TERM_TYPE'),
        };
    }

    private function failRenewal(Order $order, WalletLedger $walletLedger, string $reason): void
    {
        DB::transaction(function () use ($order, $walletLedger, $reason) {
            $lockedOrder = Order::query()->lockForUpdate()->find($order->id);
            if (!$lockedOrder || $lockedOrder->status !== 'pending_provisioning') {
                return;
            }

            $walletLedger->credit(
                $lockedOrder->user_id,
                $lockedOrder->total_minor,
                $lockedOrder->currency,
                'refund:order:' . $lockedOrder->id,
                'number_renewal_reversal',
                ['order_id' => $lockedOrder->id, 'reason' => $reason],
            );

            $lockedOrder->forceFill([
                'status' => 'failed',
                'metadata' => array_merge($lockedOrder->metadata ?? [], ['renewal_error' => $reason]),
            ])->save();
        });
    }

    public function failed(Throwable $exception): void
    {
        $order = Order::find($this->orderId);
        if (!$order || $order->status !== 'pending_provisioning') {
            return;
        }

        // A retryable provider outage must not strand the customer's funds when
        // the queue exhausts its attempts. The ledger reversal is idempotent.
        app(WalletLedger::class)->credit(
            $order->user_id,
            $order->total_minor,
            $order->currency,
            'refund:order:' . $order->id,
            'number_renewal_reversal',
            ['order_id' => $order->id, 'reason' => $exception->getMessage(), 'attempts_exhausted' => true],
        );

        Order::whereKey($order->id)
            ->where('status', 'pending_provisioning')
            ->update([
                'status' => 'failed',
                'metadata' => array_merge($order->metadata ?? [], [
                    'renewal_error' => $exception->getMessage(),
                    'attempts_exhausted' => true,
                ]),
            ]);
    }
}
