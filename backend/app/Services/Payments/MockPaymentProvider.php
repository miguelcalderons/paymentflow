<?php

namespace App\Services\Payments;

class MockPaymentProvider
{
  public function charge(int $amount, string $currency): array
  {
    return [
      'success' => true,
      'provider_reference' => 'MOCK-' . uniqid(),
    ];
  }
}
