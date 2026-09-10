<?php

namespace Tests\Feature;

use App\Services\Payments\MockPaymentProvider;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MockPaymentProviderTest extends TestCase
{
    public function test_same_idempotency_key_returns_same_provider_transaction(): void
    {
        config(['cache.default' => 'array']);

        Cache::flush();

        $provider = new MockPaymentProvider('success');

        $first = $provider->charge(
            25000,
            'USD',
            'PAY-123'
        );

        $second = $provider->charge(
            25000,
            'USD',
            'PAY-123'
        );

        $this->assertSame(
            $first['provider_reference'],
            $second['provider_reference']
        );
    }
}
