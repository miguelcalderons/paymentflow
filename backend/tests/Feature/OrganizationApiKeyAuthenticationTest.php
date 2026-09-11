<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\OrganizationApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationApiKeyAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_without_api_key_is_rejected(): void
    {
        [$organization, $customer] = $this->createOrganizationAndCustomer();

        $this->postJson(
            "/api/organizations/{$organization->id}/customers/{$customer->id}/payments",
            [
                'amount' => 25000,
                'currency' => 'USD',
            ],
            [
                'Idempotency-Key' => 'test-123',
            ]
        )->assertUnauthorized();
    }

    public function test_request_with_invalid_api_key_is_rejected(): void
    {
        [$organization, $customer] = $this->createOrganizationAndCustomer();

        $this->postJson(
            "/api/organizations/{$organization->id}/customers/{$customer->id}/payments",
            [
                'amount' => 25000,
                'currency' => 'USD',
            ],
            [
                'Authorization' => 'Bearer pf_invalid',
                'Idempotency-Key' => 'test-123',
            ]
        )->assertUnauthorized();
    }

    public function test_revoked_api_key_is_rejected(): void
    {
        [$organization, $customer] = $this->createOrganizationAndCustomer();

        $plainTextKey = 'pf_revoked_test';

        OrganizationApiKey::create([
            'organization_id' => $organization->id,
            'name' => 'test',
            'key_hash' => hash('sha256', $plainTextKey),
            'revoked_at' => now(),
        ]);

        $this->postJson(
            "/api/organizations/{$organization->id}/customers/{$customer->id}/payments",
            [
                'amount' => 25000,
                'currency' => 'USD',
            ],
            [
                'Authorization' => 'Bearer ' . $plainTextKey,
                'Idempotency-Key' => 'test-123',
            ]
        )->assertUnauthorized();
    }

    private function createOrganizationAndCustomer(): array
    {
        $organization = Organization::create([
            'name' => 'ABC Consulting',
        ]);

        $customer = Customer::create([
            'organization_id' => $organization->id,
            'name' => 'John Doe',
        ]);

        return [$organization, $customer];
    }
}
