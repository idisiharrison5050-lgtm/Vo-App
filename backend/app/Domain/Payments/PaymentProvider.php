<?php

namespace App\Domain\Payments;

interface PaymentProvider
{
    public function createIntent(array $payment): array;

    public function verifyWebhook(array $headers, string $payload): bool;

    public function parseWebhook(string $payload): array;
}
