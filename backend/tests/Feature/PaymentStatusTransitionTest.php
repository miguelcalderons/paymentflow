<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\Payment;
use App\PaymentStatus;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_payment_can_move_to_processing(): void
    {
        $payment = $this->createPayment();

        $payment->transitionTo(PaymentStatus::Processing);

        $this->assertSame(
            PaymentStatus::Processing,
            $payment->fresh()->status
        );
    }

    public function test_processing_payment_can_succeed(): void
    {
        $payment = $this->createPayment();

        $payment->transitionTo(PaymentStatus::Processing);
        $payment->transitionTo(PaymentStatus::Succeeded);

        $this->assertSame(
            PaymentStatus::Succeeded,
            $payment->fresh()->status
        );
    }

    public function test_succeeded_payment_cannot_return_to_pending(): void
    {
        $payment = $this->createPayment();

        $payment->transitionTo(PaymentStatus::Processing);
        $payment->transitionTo(PaymentStatus::Succeeded);

        $this->expectException(DomainException::class);

        $payment->transitionTo(PaymentStatus::Pending);
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
            'amount' => 25000,
            'currency' => 'USD',
            'status' => PaymentStatus::Pending,
        ]);
    }
}
