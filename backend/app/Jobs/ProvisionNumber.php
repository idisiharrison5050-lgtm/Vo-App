<?php

namespace App\Jobs;

use App\Domain\Providers\ProviderException;
use App\Domain\Providers\ProviderManager;
use App\Domain\Wallet\WalletLedger;
use App\Models\NumberAssignment;
use App\Models\Order;
use App\Models\PhoneNumber;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\InteractsWithQueue;
use Illuminate\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProvisionNumber implements ShouldQueue
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

        $phone = $order->phoneNumber;
        $provider = $phone ? $phone->provider : null;
        if (!$phone || !$provider) {
            $this->failProvisioning($order, $walletLedger, 'Missing number/provider for provisioning.');
            return;
        }

        try {
            $result = $providers->driver($provider)->provision([
                'phone_number' => $phone,
                'order' => $order,
                'country_id' => $phone->country_id,
            ]);
        } catch (ProviderException $exception) {
            if ($exception->retryable) {
                throw $exception;
            }

            $this->failProvisioning($order, $walletLedger, $exception->getMessage());
            return;
        }

        DB::transaction(function () use ($order, $phone, $result) {
            $lockedOrder = Order::query()->lockForUpdate()->find($order->id);
            if (!$lockedOrder || $lockedOrder->status !== 'pending_provisioning') {
                return;
            }

            $lockedPhone = PhoneNumber::query()->lockForUpdate()->find($phone->id);
            $assignment = NumberAssignment::query()
                ->where('order_id', $lockedOrder->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedPhone || !$assignment) {
                throw new \RuntimeException('Provisioning state is incomplete.');
            }

            $lockedPhone->forceFill([
                'status' => 'assigned',
                'provider_reference' => $result['provider_reference'] ?? $lockedPhone->provider_reference,
                'reservation_token' => null,
                'reserved_until' => null,
            ])->save();

            $assignment->forceFill(['status' => 'active'])->save();
            $lockedOrder->forceFill([
                'status' => 'completed',
                'completed_at' => now(),
                'metadata' => array_merge($lockedOrder->metadata ?? [], [
                    'provisioning' => 'provider',
                    'provider_reference' => $result['provider_reference'] ?? null,
                ]),
            ])->save();
        });
    }

    private function failProvisioning(Order $order, WalletLedger $walletLedger, string $reason): void
    {
        DB::transaction(function () use ($order, $walletLedger, $reason) {
            $lockedOrder = Order::query()->lockForUpdate()->find($order->id);
            if (!$lockedOrder || $lockedOrder->status !== 'pending_provisioning') {
                return;
            }

            $phone = $lockedOrder->phoneNumber()->lockForUpdate()->first();
            $assignment = NumberAssignment::query()
                ->where('order_id', $lockedOrder->id)
                ->lockForUpdate()
                ->first();

            if ($phone) {
                $phone->forceFill([
                    'status' => 'available',
                    'reservation_token' => null,
                    'reserved_until' => null,
                ])->save();
            }

            if ($assignment) {
                $assignment->forceFill(['status' => 'failed', 'released_at' => now()])->save();
            }

            $walletLedger->credit(
                $lockedOrder->user_id,
                $lockedOrder->total_minor,
                $lockedOrder->currency,
                'refund:order:' . $lockedOrder->id,
                'number_purchase_reversal',
                ['order_id' => $lockedOrder->id, 'reason' => $reason],
            );

            $lockedOrder->forceFill([
                'status' => 'failed',
                'metadata' => array_merge($lockedOrder->metadata ?? [], ['provisioning_error' => $reason]),
            ])->save();
        });
    }

    public function failed(Throwable $exception): void
    {
        $order = Order::find($this->orderId);
        if (!$order || $order->status !== 'pending_provisioning') {
            return;
        }

        $this->failProvisioning(
            $order,
            app(WalletLedger::class),
            'Provider provisioning failed after all retries: ' . $exception->getMessage(),
        );
    }
}
