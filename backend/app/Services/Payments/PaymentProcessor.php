<?php

namespace App\Services\Payments;

use App\Exceptions\RetryablePaymentException;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\PaymentStatus;
use App\Contracts\PaymentProviderInterface;
use Illuminate\Support\Facades\Log;

class PaymentProcessor
{
    public function __construct(
        private PaymentProviderInterface $provider
    ) {}

    public function process(Payment $payment): PaymentAttempt
    {
        Log::info('Payment processing started', [
            'payment_id' => $payment->id,
            'payment_reference' => $payment->reference,
            'organization_id' => $payment->organization_id,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'status' => $payment->status->value,
        ]);

        if ($payment->status === PaymentStatus::Pending) {
            $payment->transitionTo(PaymentStatus::Processing);
        }

        if ($payment->status !== PaymentStatus::Processing) {
            throw new \DomainException(
                "Cannot process payment with status {$payment->status->value}"
            );
        }

        try {
            Log::info('Calling payment provider', [
                'payment_id' => $payment->id,
                'payment_reference' => $payment->reference,
            ]);
            $result = $this->provider->charge(
                $payment->amount,
                $payment->currency,
                $payment->reference
            );
            Log::info('Payment provider responded', [
                'payment_id' => $payment->id,
                'payment_reference' => $payment->reference,
                'success' => $result['success'],
                'provider_reference' => $result['provider_reference'],
            ]);
        } catch (RetryablePaymentException $e) {
            Log::warning('Payment provider temporary failure', [
                'payment_id' => $payment->id,
                'payment_reference' => $payment->reference,
                'error' => $e->getMessage(),
            ]);

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
        Log::info('Payment processing finished', [
            'payment_id' => $payment->id,
            'payment_reference' => $payment->reference,
            'status' => $payment->fresh()->status->value,
        ]);
        return $attempt;
    }
}
