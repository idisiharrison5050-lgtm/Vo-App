<?php

namespace App\Domain\Payments;

use InvalidArgumentException;

class PaymentManager
{
    /** @var array<string, PaymentProvider> */
    private array $drivers = [];

    public function register(string $name, PaymentProvider $driver): void
    {
        $this->drivers[$name] = $driver;
    }

    public function driver(string $name): PaymentProvider
    {
        if (!isset($this->drivers[$name])) {
            throw new InvalidArgumentException('PAYMENT_PROVIDER_NOT_CONFIGURED');
        }

        return $this->drivers[$name];
    }
}
