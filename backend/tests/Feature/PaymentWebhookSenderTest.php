<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\Payment;
use App\PaymentStatus;
use App\Services\Webhooks\PaymentWebhookSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentWebhookSenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_signed_payment_webhook_to_organization(): void
    {
        Http::fake();

        $organization = Organization::create([
            'name' => 'ABC Consulting',
            'webhook_url' => 'https://shop.example.com/webhooks/paymentflow',
            'webhook_secret' => 'secret-123',
        ]);

        $customer = Customer::create([
            'organization_id' => $organization->id,
            'name' => 'John Doe',
        ]);

        $payment = Payment::create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'reference' => 'PAY-OUTBOUND-123',
            'amount' => 25000,
            'currency' => 'USD',
            'status' => PaymentStatus::Succeeded,
        ]);

        $sender = new PaymentWebhookSender();

        $sender->send($payment);

        Http::assertSent(function ($request) use ($organization) {
            $payload = [
                'event' => 'payment.succeeded',
                'payment_reference' => 'PAY-OUTBOUND-123',
                'status' => 'succeeded',
                'amount' => 25000,
                'currency' => 'USD',
            ];

            $json = json_encode($payload);

            $expectedSignature = hash_hmac(
                'sha256',
                $json,
                $organization->webhook_secret
            );

            return $request->url() === $organization->webhook_url
                && $request['event'] === 'payment.succeeded'
                && $request['payment_reference'] === 'PAY-OUTBOUND-123'
                && $request->hasHeader(
                    'X-PaymentFlow-Signature',
                    $expectedSignature
                );
        });
    }
}
