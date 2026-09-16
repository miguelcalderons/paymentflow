<?php

namespace App\Contracts;

interface PaymentProviderInterface
{
    public function charge(
        int $amount,
        string $currency,
        string $idempotencyKey
    ): array;
}
