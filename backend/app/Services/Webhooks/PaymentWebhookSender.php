<?php

namespace App\Services\Webhooks;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;

class PaymentWebhookSender
{
  public function send(Payment $payment): void
  {
    $organization = $payment->organization;

    if (!$organization->webhook_url || !$organization->webhook_secret) {
      return;
    }

    $payload = [
      'event' => 'payment.' . $payment->status->value,
      'payment_reference' => $payment->reference,
      'status' => $payment->status->value,
      'amount' => $payment->amount,
      'currency' => $payment->currency,
    ];

    $json = json_encode($payload);

    $signature = hash_hmac(
      'sha256',
      $json,
      $organization->webhook_secret
    );
    Http::timeout(5)
      ->withHeaders([
        'X-PaymentFlow-Signature' => $signature,
        'Content-Type' => 'application/json',
      ])
      ->withBody(
        $json,
        'application/json'
      )
      ->post($organization->webhook_url)
      ->throw();
  }
}
