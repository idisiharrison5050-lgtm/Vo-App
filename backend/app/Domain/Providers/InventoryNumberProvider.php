<?php

namespace App\Domain\Providers;

use App\Models\PhoneNumber;
use RuntimeException;

/**
 * Internal inventory adapter used for development and controlled deployments.
 * It deliberately does not emulate a carrier API.
 */
class InventoryNumberProvider implements NumberProvider
{
    public function search(array $requirements): array
    {
        $query = PhoneNumber::query()->where('status', 'available');

        if (!empty($requirements['country_id'])) {
            $query->where('country_id', $requirements['country_id']);
        }

        if (!empty($requirements['provider_id'])) {
            $query->where('provider_id', $requirements['provider_id']);
        }

        return $query->limit((int) ($requirements['limit'] ?? 20))->get()->all();
    }

    public function provision(array $requirements): array
    {
        $phone = $requirements['phone_number'] ?? null;

        if (!$phone instanceof PhoneNumber) {
            throw new ProviderException('Inventory provisioning requires a PhoneNumber.', false, 'INVALID_INPUT');
        }

        if ($phone->status !== 'available' && $phone->status !== 'reserved') {
            throw new ProviderException('Phone number is not provisionable.', false, 'NUMBER_NOT_AVAILABLE');
        }

        return [
            'provider_reference' => $phone->provider_reference ?: 'inventory:' . $phone->id,
            'number' => $phone->number,
            'capabilities' => $phone->capabilities ?? [],
        ];
    }

    public function renew(string $providerReference, int $durationDays): array
    {
        if ($durationDays < 1) {
            throw new ProviderException('Renewal duration must be at least one day.', false, 'INVALID_DURATION');
        }

        return [
            'provider_reference' => $providerReference,
            'duration_days' => $durationDays,
        ];
    }

    public function release(string $providerReference): void
    {
        if ($providerReference === '') {
            throw new ProviderException('Provider reference is required.', false, 'INVALID_REFERENCE');
        }
    }

    public function health(): array
    {
        return [
            'healthy' => true,
            'driver' => 'inventory',
            'checked_at' => now()->toISOString(),
        ];
    }
}
