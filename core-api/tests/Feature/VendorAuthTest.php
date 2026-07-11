<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VendorAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_can_register_successfully()
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'John Vendor',
            'email' => 'john@vendor.com',
            'password' => 'password123',
            'type' => 'vendor',
            'company_name' => 'Johns Events LLC',
            'contact_person' => 'John Manager',
            'phone' => '123456789',
            'address' => 'Dubai Marina',
            'website' => 'https://johnsevents.com',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'user' => [
                    'id', 'name', 'email', 'type', 'vendor' => [
                        'id', 'company_name', 'contact_person', 'email', 'kyc_status'
                    ]
                ],
                'token'
            ],
            'message'
        ]);

        $this->assertDatabaseHas('users', ['email' => 'john@vendor.com', 'type' => 'vendor']);
        $this->assertDatabaseHas('vendors', ['email' => 'john@vendor.com', 'company_name' => 'Johns Events LLC', 'kyc_status' => 'pending']);
    }

    public function test_vendor_registration_requires_company_and_contact()
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'John Vendor',
            'email' => 'john@vendor.com',
            'password' => 'password123',
            'type' => 'vendor',
            // Missing company_name and contact_person
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['company_name', 'contact_person']);
    }

    public function test_vendor_login_loads_profile()
    {
        // First register a vendor
        $this->postJson('/api/v1/register', [
            'name' => 'John Vendor',
            'email' => 'john@vendor.com',
            'password' => 'password123',
            'type' => 'vendor',
            'company_name' => 'Johns Events LLC',
            'contact_person' => 'John Manager',
        ]);

        // Login
        $response = $this->postJson('/api/v1/login', [
            'email' => 'john@vendor.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'user' => [
                    'id', 'name', 'email', 'type', 'vendor' => [
                        'id', 'company_name', 'contact_person', 'email'
                    ]
                ],
                'token'
            ],
            'message'
        ]);
    }

    public function test_authenticated_vendor_user_route_loads_profile()
    {
        // First register a vendor
        $registerRes = $this->postJson('/api/v1/register', [
            'name' => 'John Vendor',
            'email' => 'john@vendor.com',
            'password' => 'password123',
            'type' => 'vendor',
            'company_name' => 'Johns Events LLC',
            'contact_person' => 'John Manager',
        ]);

        $token = $registerRes['data']['token'];

        // Access user route
        $response = $this->getJson('/api/v1/user', [
            'Authorization' => 'Bearer ' . $token
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id', 'name', 'email', 'type', 'vendor' => [
                    'id', 'company_name', 'contact_person', 'email'
                ]
            ]
        ]);
    }

    public function test_vendor_can_create_event_and_it_associates_vendor_id()
    {
        $registerRes = $this->postJson('/api/v1/register', [
            'name' => 'John Vendor',
            'email' => 'john@vendor.com',
            'password' => 'password123',
            'type' => 'vendor',
            'company_name' => 'Johns Events LLC',
            'contact_person' => 'John Manager',
        ]);
        $token = $registerRes['data']['token'];

        $response = $this->postJson('/api/v1/events', [
            'title' => 'Big Music Festival',
            'description' => 'A large summer music festival',
            'location' => 'Dubai Beach',
            'event_date' => '2026-08-20 18:00:00',
        ], [
            'Authorization' => 'Bearer ' . $token
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment(['title' => 'Big Music Festival']);
        $this->assertDatabaseHas('events', [
            'title' => 'Big Music Festival',
            'vendor_id' => $registerRes['data']['user']['vendor']['id']
        ]);
    }

    public function test_events_can_be_filtered_by_vendor()
    {
        // 1. Create Vendor A
        $vendorARes = $this->postJson('/api/v1/register', [
            'name' => 'Vendor A',
            'email' => 'vendora@example.com',
            'password' => 'password123',
            'type' => 'vendor',
            'company_name' => 'Company A',
            'contact_person' => 'Manager A',
        ]);
        $tokenA = $vendorARes['data']['token'];
        $vendorAId = $vendorARes['data']['user']['vendor']['id'];

        // 2. Create Vendor B
        $vendorBRes = $this->postJson('/api/v1/register', [
            'name' => 'Vendor B',
            'email' => 'vendorb@example.com',
            'password' => 'password123',
            'type' => 'vendor',
            'company_name' => 'Company B',
            'contact_person' => 'Manager B',
        ]);
        $vendorBId = $vendorBRes['data']['user']['vendor']['id'];

        // 3. Create Event for Vendor A
        $this->postJson('/api/v1/events', [
            'title' => 'Event A',
            'location' => 'Location A',
            'event_date' => '2026-08-20 18:00:00',
        ], [
            'Authorization' => 'Bearer ' . $tokenA
        ]);

        // 4. Retrieve events filtering by Vendor A
        $responseA = $this->getJson('/api/v1/events?vendor_id=' . $vendorAId);
        $responseA->assertStatus(200);
        $this->assertCount(1, $responseA->json('data'));
        $responseA->assertJsonFragment(['title' => 'Event A']);

        // 5. Retrieve events filtering by Vendor B
        $responseB = $this->getJson('/api/v1/events?vendor_id=' . $vendorBId);
        $responseB->assertStatus(200);
        $this->assertCount(0, $responseB->json('data'));
    }
}
