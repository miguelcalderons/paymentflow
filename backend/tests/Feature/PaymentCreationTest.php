<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\User;
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
            ]
        );

        $response
            ->assertCreated()
            ->assertJson([
                'organization_id' => $organization->id,
                'customer_id' => $customer->id,
                'amount' => 25000,
                'currency' => 'USD',
                'status' => 'pending',
            ]);

        $this->assertDatabaseHas('payments', [
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'amount' => 25000,
            'status' => 'pending',
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
}
