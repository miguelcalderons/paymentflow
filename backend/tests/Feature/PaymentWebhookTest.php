<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Organization;
use App\Models\Customer;
use App\Models\Payment;
use App\PaymentStatus;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_webhook_is_stored(): void
    {
        config([
            'services.mock_payment.webhook_secret' => 'test-secret',
        ]);

        $payload = [
            'event_id' => 'evt_123',
            'type' => 'payment.updated',
            'data' => [
                'payment_reference' => 'PAY-ABC123',
            ],
        ];

        $json = json_encode($payload);

        $signature = hash_hmac(
            'sha256',
            $json,
            'test-secret'
        );

        $response = $this->call(
            'POST',
            '/api/webhooks/mock',
            [],
            [],
            [],
            [
                'HTTP_X_MOCK_SIGNATURE' => $signature,
                'CONTENT_TYPE' => 'application/json',
            ],
            $json
        );

        $response
            ->assertOk()
            ->assertJson([
                'status' => 'received',
                'event_id' => 'evt_123',
            ]);

        $this->assertDatabaseHas('webhook_events', [
            'provider' => 'mock',
            'event_id' => 'evt_123',
            'type' => 'payment.updated',
        ]);
    }

    public function test_invalid_webhook_signature_is_rejected(): void
    {
        config([
            'services.mock_payment.webhook_secret' => 'test-secret',
        ]);

        $response = $this->postJson(
            '/api/webhooks/mock',
            [
                'event_id' => 'evt_bad',
                'type' => 'payment.succeeded',
                'data' => [
                    'payment_reference' => 'PAY-ABC123',
                ],
            ],
            [
                'X-Mock-Signature' => 'invalid-signature',
            ]
        );

        $response->assertUnauthorized();

        $this->assertDatabaseCount('webhook_events', 0);
    }

    public function test_duplicate_webhook_is_not_processed_twice(): void
    {
        config([
            'services.mock_payment.webhook_secret' => 'test-secret',
        ]);

        $payload = [
            'event_id' => 'evt_duplicate',
            'type' => 'payment.updated',
            'data' => [
                'payment_reference' => 'PAY-ABC123',
            ],
        ];

        $json = json_encode($payload);

        $signature = hash_hmac(
            'sha256',
            $json,
            'test-secret'
        );

        $headers = [
            'HTTP_X_MOCK_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ];

        $first = $this->call(
            'POST',
            '/api/webhooks/mock',
            [],
            [],
            [],
            $headers,
            $json
        );

        $second = $this->call(
            'POST',
            '/api/webhooks/mock',
            [],
            [],
            [],
            $headers,
            $json
        );

        $first->assertOk()->assertJson([
            'status' => 'received',
        ]);

        $second->assertOk()->assertJson([
            'status' => 'duplicate',
        ]);

        $this->assertDatabaseCount('webhook_events', 1);
    }

    public function test_payment_succeeded_webhook_updates_payment(): void
    {
        config([
            'services.mock_payment.webhook_secret' => 'test-secret',
        ]);

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
            'reference' => 'PAY-WEBHOOK-123',
            'amount' => 25000,
            'currency' => 'USD',
            'status' => PaymentStatus::Processing,
        ]);

        $payload = [
            'event_id' => 'evt_payment_succeeded',
            'type' => 'payment.succeeded',
            'data' => [
                'payment_reference' => $payment->reference,
            ],
        ];

        $json = json_encode($payload);

        $signature = hash_hmac(
            'sha256',
            $json,
            'test-secret'
        );

        $response = $this->call(
            'POST',
            '/api/webhooks/mock',
            [],
            [],
            [],
            [
                'HTTP_X_MOCK_SIGNATURE' => $signature,
                'CONTENT_TYPE' => 'application/json',
            ],
            $json
        );

        $response->assertOk();

        $this->assertSame(
            PaymentStatus::Succeeded,
            $payment->fresh()->status
        );

        $this->assertDatabaseHas('webhook_events', [
            'event_id' => 'evt_payment_succeeded',
            'type' => 'payment.succeeded',
        ]);

        $this->assertNotNull(
            \App\Models\WebhookEvent::where(
                'event_id',
                'evt_payment_succeeded'
            )->first()->processed_at
        );
    }

    public function test_payment_failed_webhook_updates_payment(): void
    {
        config([
            'services.mock_payment.webhook_secret' => 'test-secret',
        ]);

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
            'reference' => 'PAY-WEBHOOK-FAILED',
            'amount' => 25000,
            'currency' => 'USD',
            'status' => PaymentStatus::Processing,
        ]);

        $payload = [
            'event_id' => 'evt_payment_failed',
            'type' => 'payment.failed',
            'data' => [
                'payment_reference' => $payment->reference,
            ],
        ];

        $json = json_encode($payload);

        $signature = hash_hmac(
            'sha256',
            $json,
            'test-secret'
        );

        $response = $this->call(
            'POST',
            '/api/webhooks/mock',
            [],
            [],
            [],
            [
                'HTTP_X_MOCK_SIGNATURE' => $signature,
                'CONTENT_TYPE' => 'application/json',
            ],
            $json
        );

        $response->assertOk();

        $this->assertSame(
            PaymentStatus::Failed,
            $payment->fresh()->status
        );
    }
}
