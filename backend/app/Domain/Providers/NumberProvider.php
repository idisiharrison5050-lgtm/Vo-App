<?php

namespace App\Domain\Providers;

interface NumberProvider
{
    public function search(array $requirements): array;

    public function provision(array $requirements): array;

    /**
     * Renew an assigned number. Providers should use the operation reference
     * as their idempotency key when their API supports idempotent mutations.
     */
    public function renew(string $providerReference, int $durationDays, ?string $operationReference = null): array;

    public function release(string $providerReference): void;

    public function health(): array;
}
