<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_can_be_created(): void
    {
        $organization = Organization::create([
            'name' => 'ABC Consulting',
        ]);

        $customer = Customer::create([
            'organization_id' => $organization->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response = $this->postJson(
            "/api/organizations/{$organization->id}/customers/{$customer->id}/payments",
            [
                'amount' => 25000,
                'currency' => 'USD',
                'description' => 'Website development payment',
            ],
            [
                'Idempotency-Key' => 'payment-test-123',
            ]
        );

        $response->assertCreated();

        $this->assertDatabaseHas('payments', [
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'amount' => 25000,
            'status' => 'pending',
            'idempotency_key' => 'payment-test-123',
        ]);
    }

    public function test_negative_amount_is_rejected(): void
    {
        $organization = Organization::create([
            'name' => 'ABC Consulting',
        ]);

        $customer = Customer::create([
            'organization_id' => $organization->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response = $this->postJson(
            "/api/organizations/{$organization->id}/customers/{$customer->id}/payments",
            [
                'amount' => -500,
                'currency' => 'USD',
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_customer_cannot_be_used_with_another_organization(): void
    {
        $organizationA = Organization::create([
            'name' => 'ABC Consulting',
        ]);

        $organizationB = Organization::create([
            'name' => 'XYZ Company',
        ]);

        $customer = Customer::create([
            'organization_id' => $organizationA->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response = $this->postJson(
            "/api/organizations/{$organizationB->id}/customers/{$customer->id}/payments",
            [
                'amount' => 25000,
                'currency' => 'USD',
            ]
        );

        $response->assertNotFound();
    }

    public function test_same_idempotency_key_returns_same_payment(): void
    {
        $organization = Organization::create([
            'name' => 'ABC Consulting',
        ]);

        $customer = Customer::create([
            'organization_id' => $organization->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $headers = [
            'Idempotency-Key' => 'checkout-test-123',
        ];

        $payload = [
            'amount' => 25000,
            'currency' => 'USD',
        ];

        $first = $this->postJson(
            "/api/organizations/{$organization->id}/customers/{$customer->id}/payments",
            $payload,
            $headers
        );

        $second = $this->postJson(
            "/api/organizations/{$organization->id}/customers/{$customer->id}/payments",
            $payload,
            $headers
        );

        $first->assertCreated();
        $second->assertOk();

        $this->assertSame(
            $first->json('id'),
            $second->json('id')
        );

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_idempotency_key_is_required(): void
    {
        $organization = Organization::create([
            'name' => 'ABC Consulting',
        ]);

        $customer = Customer::create([
            'organization_id' => $organization->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response = $this->postJson(
            "/api/organizations/{$organization->id}/customers/{$customer->id}/payments",
            [
                'amount' => 25000,
                'currency' => 'USD',
            ]
        );

        $response->assertStatus(422);

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_same_idempotency_key_with_different_amount_is_rejected(): void
    {
        $organization = Organization::create([
            'name' => 'ABC Consulting',
        ]);

        $customer = Customer::create([
            'organization_id' => $organization->id,
            'name' => 'John Doe',
        ]);

        $headers = [
            'Idempotency-Key' => 'checkout-conflict-123',
        ];

        $this->postJson(
            "/api/organizations/{$organization->id}/customers/{$customer->id}/payments",
            [
                'amount' => 25000,
                'currency' => 'USD',
            ],
            $headers
        )->assertCreated();

        $this->postJson(
            "/api/organizations/{$organization->id}/customers/{$customer->id}/payments",
            [
                'amount' => 50000,
                'currency' => 'USD',
            ],
            $headers
        )->assertStatus(409);

        $this->assertDatabaseCount('payments', 1);
    }
}
