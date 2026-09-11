<?php

namespace App\Domain\Providers;

use App\Models\Provider;
use InvalidArgumentException;

class ProviderManager
{
    /** @var array<string, NumberProvider> */
    private array $drivers = [];

    public function register(string $name, NumberProvider $driver): void
    {
        $this->drivers[$name] = $driver;
    }

    public function driver(Provider $provider): NumberProvider
    {
        $configured = config('providers.drivers.' . $provider->slug);
        $driverName = is_array($configured) ? ($configured['driver'] ?? null) : null;

        if (!$driverName || !isset($this->drivers[$driverName])) {
            throw new InvalidArgumentException(
                sprintf('No number provider driver is configured for [%s].', $provider->slug)
            );
        }

        return $this->drivers[$driverName];
    }

    /** @return array<string, NumberProvider> */
    public function drivers(): array
    {
        return $this->drivers;
    }
}
