<?php

namespace App\Domain\Payments;

use RuntimeException;

class PaymentException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly bool $retryable = true,
        public readonly ?string $providerCode = null,
    ) {
        parent::__construct($message);
    }
}
