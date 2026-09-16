<?php

namespace App\Services\Webhooks;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

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

    Log::info('Outbound payment webhook sending', [
      'payment_id' => $payment->id,
      'payment_reference' => $payment->reference,
      'organization_id' => $organization->id,
      'event' => $payload['event'],
    ]);

    try {
      $response = Http::timeout(5)
        ->withHeaders([
          'X-PaymentFlow-Signature' => $signature,
          'Content-Type' => 'application/json',
        ])
        ->withBody($json, 'application/json')
        ->post($organization->webhook_url)
        ->throw();

      Log::info('Outbound payment webhook delivered', [
        'payment_id' => $payment->id,
        'payment_reference' => $payment->reference,
        'organization_id' => $organization->id,
        'event' => $payload['event'],
        'http_status' => $response->status(),
      ]);
    } catch (Throwable $e) {
      Log::warning('Outbound payment webhook failed', [
        'payment_id' => $payment->id,
        'payment_reference' => $payment->reference,
        'organization_id' => $organization->id,
        'event' => $payload['event'],
        'error' => $e->getMessage(),
      ]);

      throw $e;
    }
  }
}
