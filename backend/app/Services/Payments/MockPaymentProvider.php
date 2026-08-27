<?php

namespace App\Services\Payments;

class MockPaymentProvider
{
  public function __construct(
    private bool $shouldSucceed = true
  ) {}

  public function charge(int $amount, string $currency): array
  {
    if (! $this->shouldSucceed) {
      return [
        'success' => false,
        'provider_reference' => 'MOCK-' . uniqid(),
        'failure_reason' => 'Mock provider failure',
      ];
    }

    return [
      'success' => true,
      'provider_reference' => 'MOCK-' . uniqid(),
      'failure_reason' => null,
    ];
  }
}
