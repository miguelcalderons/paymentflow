<?php

namespace App\Services\Payments;

use App\Exceptions\RetryablePaymentException;

class MockPaymentProvider
{
    public function __construct(
        private string $mode = 'success'
    ) {}

    public function charge(int $amount, string $currency): array
    {
        return match ($this->mode) {
            'success' => [
                'success' => true,
                'provider_reference' => 'MOCK-' . uniqid(),
                'failure_reason' => null,
            ],

            'declined' => [
                'success' => false,
                'provider_reference' => 'MOCK-' . uniqid(),
                'failure_reason' => 'Card declined',
            ],

            'timeout' => throw new RetryablePaymentException(
                'Mock provider timeout'
            ),

            default => throw new \InvalidArgumentException(
                "Unknown mock provider mode: {$this->mode}"
            ),
        };
    }
}
