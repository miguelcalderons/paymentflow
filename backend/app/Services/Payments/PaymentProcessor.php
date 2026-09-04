<?php

namespace App\Services\Payments;

use App\Exceptions\RetryablePaymentException;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\PaymentStatus;

class PaymentProcessor
{
    public function __construct(
        private MockPaymentProvider $provider
    ) {}

    public function process(Payment $payment): PaymentAttempt
    {
        if ($payment->status === PaymentStatus::Pending) {
            $payment->transitionTo(PaymentStatus::Processing);
        }

        if ($payment->status !== PaymentStatus::Processing) {
            throw new \DomainException(
                "Cannot process payment with status {$payment->status->value}"
            );
        }

        try {
            $result = $this->provider->charge(
                $payment->amount,
                $payment->currency
            );
        } catch (RetryablePaymentException $e) {
            PaymentAttempt::create([
                'payment_id' => $payment->id,
                'provider' => 'mock',
                'provider_reference' => null,
                'status' => 'failed',
                'failure_reason' => $e->getMessage(),
            ]);

            throw $e;
        }

        $attempt = PaymentAttempt::create([
            'payment_id' => $payment->id,
            'provider' => 'mock',
            'provider_reference' => $result['provider_reference'],
            'status' => $result['success'] ? 'succeeded' : 'failed',
            'failure_reason' => $result['failure_reason'] ?? null,
        ]);

        if ($result['success']) {
            $payment->transitionTo(PaymentStatus::Succeeded);
        } else {
            $payment->transitionTo(PaymentStatus::Failed);
        }

        return $attempt;
    }
}
