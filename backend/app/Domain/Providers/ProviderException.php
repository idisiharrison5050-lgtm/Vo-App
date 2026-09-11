<?php

namespace App\Domain\Providers;

use RuntimeException;

class ProviderException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly bool $retryable = true,
        public readonly ?string $providerCode = null,
    ) {
        parent::__construct($message);
    }
}
