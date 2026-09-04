<?php

namespace Tests\Feature;

use App\Jobs\ProcessPaymentJob;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Payment;
use App\PaymentStatus;
use App\Services\Payments\MockPaymentProvider;
use App\Services\Payments\PaymentProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessPaymentJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_rethrows_retryable_timeout_exception(): void
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

        $processor = new PaymentProcessor(
            new MockPaymentProvider('timeout')
        );

        $job = new ProcessPaymentJob($payment);

        $this->expectException(
            \App\Exceptions\RetryablePaymentException::class
        );

        $job->handle($processor);
    }

    public function test_job_has_retry_policy(): void
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

        $job = new ProcessPaymentJob($payment);

        $this->assertSame(3, $job->tries);

        $this->assertSame(
            [5, 30],
            $job->backoff()
        );
    }

    public function test_failed_job_marks_processing_payment_as_failed(): void
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
            'status' => PaymentStatus::Processing,
        ]);

        $job = new ProcessPaymentJob($payment);

        $job->failed(
            new \App\Exceptions\RetryablePaymentException(
                'Mock provider timeout'
            )
        );

        $this->assertSame(
            PaymentStatus::Failed,
            $payment->fresh()->status
        );
    }
}
