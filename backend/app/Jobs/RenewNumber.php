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

        if (!$assignment || !$phone || !$provider || !$reference || $days < 1) {
            $this->failRenewal($order, $walletLedger, 'Renewal state is incomplete.');
            return;
        }

        try {
            $result = $providers->driver($provider)->renew($reference, $days);
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

            $lockedAssignment->forceFill([
                'ends_at' => $lockedAssignment->ends_at->copy()->addDays((int) ($lockedOrder->metadata['duration_days'] ?? 0)),
            ])->save();

            $lockedOrder->forceFill([
                'status' => 'completed',
                'completed_at' => now(),
                'metadata' => array_merge($lockedOrder->metadata ?? [], [
                    'provider_reference' => $result['provider_reference'] ?? $lockedPhone->provider_reference,
                ]),
            ])->save();
        });
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
        // Failed jobs remain available for operational reconciliation and alerting.
    }
}
