<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Services\Webhooks\PaymentWebhookSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendPaymentWebhookJob implements ShouldQueue
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

    public function handle(PaymentWebhookSender $sender): void
    {
        $sender->send($this->payment);
    }
}
