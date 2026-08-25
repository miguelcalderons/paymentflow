<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentAttemptTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_can_have_multiple_attempts(): void
    {
        $payment = $this->createPayment();

        PaymentAttempt::create([
            'payment_id' => $payment->id,
            'provider' => 'mock',
            'provider_reference' => 'TXN-001',
            'status' => 'failed',
        ]);

        PaymentAttempt::create([
            'payment_id' => $payment->id,
            'provider' => 'mock',
            'provider_reference' => 'TXN-002',
            'status' => 'succeeded',
        ]);

        $this->assertCount(2, $payment->fresh()->attempts);
    }

    public function test_attempt_belongs_to_payment(): void
    {
        $payment = $this->createPayment();

        $attempt = PaymentAttempt::create([
            'payment_id' => $payment->id,
            'provider' => 'mock',
            'provider_reference' => 'TXN-001',
            'status' => 'failed',
        ]);

        $this->assertTrue($attempt->payment->is($payment));
    }

    public function test_deleting_payment_deletes_attempts(): void
    {
        $payment = $this->createPayment();

        PaymentAttempt::create([
            'payment_id' => $payment->id,
            'provider' => 'mock',
            'provider_reference' => 'TXN-001',
            'status' => 'failed',
        ]);

        $payment->delete();

        $this->assertDatabaseCount('payment_attempts', 0);
    }

    private function createPayment(): Payment
    {
        $organization = Organization::create([
            'name' => 'ABC Consulting',
        ]);

        $customer = Customer::create([
            'organization_id' => $organization->id,
            'name' => 'John Doe',
        ]);

        return Payment::create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'reference' => uniqid('PAY-'),
            'amount' => 50000,
            'currency' => 'USD',
            'status' => 'pending',
        ]);
    }
}
