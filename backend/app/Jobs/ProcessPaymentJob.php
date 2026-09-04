<?php

namespace App\Jobs;

use App\Exceptions\RetryablePaymentException;
use App\Models\Payment;
use App\PaymentStatus;
use App\Services\Payments\PaymentProcessor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessPaymentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public Payment $payment
    ) {}

    public function backoff(): array
    {
        return [5, 30];
    }

    public function handle(PaymentProcessor $processor): void
    {
        $processor->process($this->payment);
    }

    public function failed(?Throwable $exception): void
    {
        $payment = $this->payment->fresh();

        if (
            $payment?->status === PaymentStatus::Processing
            && $exception instanceof RetryablePaymentException
        ) {
            $payment->transitionTo(PaymentStatus::Failed);
        }
    }
}
