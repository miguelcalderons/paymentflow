<?php

namespace App\Services\Payments;

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
        $payment->transitionTo(PaymentStatus::Processing);

        $result = $this->provider->charge(
            $payment->amount,
            $payment->currency
        );

        $attempt = PaymentAttempt::create([
            'payment_id' => $payment->id,
            'provider' => 'mock',
            'provider_reference' => $result['provider_reference'],
            'status' => $result['success'] ? 'succeeded' : 'failed',
        ]);

        if ($result['success']) {
            $payment->transitionTo(PaymentStatus::Succeeded);
        } else {
            $payment->transitionTo(PaymentStatus::Failed);
        }

        return $attempt;
    }
}
