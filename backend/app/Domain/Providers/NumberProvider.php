<?php

namespace App\Domain\Providers;

interface NumberProvider
{
    public function search(array $requirements): array;

    public function provision(array $requirements): array;

    public function renew(string $providerReference, int $durationDays): array;

    public function release(string $providerReference): void;

    public function health(): array;
}
