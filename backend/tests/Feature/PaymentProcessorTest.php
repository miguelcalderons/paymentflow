<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Customer;
use App\Models\Payment;
use App\PaymentStatus;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Payments\PaymentProcessor;
use App\Services\Payments\MockPaymentProvider;

class PaymentProcessorTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_processor_creates_successful_attempt(): void
    {
        $organization = Organization::create([
            'name' => 'ABC Consulting',
        ]);

        $customer = Customer::create([
            'organization_id' => $organization->id,
            'name' => 'John Doe',
        ]);

        $payment = Payment::create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'reference' => uniqid('PAY-'),
            'amount' => 25000,
            'currency' => 'USD',
            'status' => PaymentStatus::Pending,
        ]);

        $processor = new PaymentProcessor(new MockPaymentProvider());

        $attempt = $processor->process($payment);

        $this->assertSame(
            PaymentStatus::Succeeded,
            $payment->fresh()->status
        );

        $this->assertSame('succeeded', $attempt->status);

        $this->assertTrue($attempt->payment->is($payment));

        $this->assertNotNull($attempt->provider_reference);

        $this->assertStringStartsWith(
            'MOCK-',
            $attempt->provider_reference
        );

        $this->assertDatabaseHas('payment_attempts', [
            'id' => $attempt->id,
            'payment_id' => $payment->id,
            'provider' => 'mock',
            'status' => 'succeeded',
            'provider_reference' => $attempt->provider_reference,
        ]);
    }
}
