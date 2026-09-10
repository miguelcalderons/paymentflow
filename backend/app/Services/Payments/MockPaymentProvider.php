<?php

namespace App\Services\Payments;

use App\Exceptions\RetryablePaymentException;
use Illuminate\Support\Facades\Cache;

class MockPaymentProvider
{
    public function __construct(
        private string $mode = 'success'
    ) {}

    public function charge(
        int $amount,
        string $currency,
        string $idempotencyKey
    ): array {
        $cacheKey = "mock-payment:{$idempotencyKey}";

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $result = match ($this->mode) {
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

        Cache::forever($cacheKey, $result);

        return $result;
    }
}
