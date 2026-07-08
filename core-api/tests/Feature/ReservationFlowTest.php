<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\TicketType;
use App\Models\Event;
use App\Models\User;
use Tests\TestCase;

class ReservationFlowTest extends TestCase
{
    use RefreshDatabase; 

    public function test_user_can_reserve_and_finalize_tickets()
    {
        // 1. Arrange: Create a user and authenticate them
        $user = User::factory()->create();

        // 1. Arrange: Create the parent Event
        $event = Event::create([
            'title'      => 'Tech Conference 2026',
            'slug'       => 'tech-conf-2026',
            'start_time' => now(),
            'end_time'   => now()->addHours(2),
            'status'     => 'published',
        ]); 
        
        // Create the TicketType
        $ticketType = TicketType::create([
            'event_id'            => $event->id,
            'name'                => 'VIP Ticket',
            'price_cents'         => 1000,
            'total_capacity'      => 100,
            'remaining_inventory' => 10,
        ]);

        // 2. Act: Reserve the ticket while acting as the authenticated user
        $response = $this->actingAs($user)->postJson('/api/v1/checkout/reserve', [
            'ticket_type_id' => $ticketType->id,
            'quantity'       => 2
        ]);

        $response->assertStatus(201);
        $token = $response->json('data.session_token');

        // 3. Act: Confirm the purchase
        $confirmResponse = $this->actingAs($user)->postJson('/api/v1/checkout/confirm', [
            'session_token' => $token
        ]);

        // If the API returns anything other than 200, debug and fail
        if ($confirmResponse->status() !== 200) {
            $confirmResponse->dump();
            $this->fail("Checkout confirmation failed with status: " . $confirmResponse->status());
        }

        $confirmResponse->assertStatus(200);

        // 4. Assert: Database reflects the correct state
        $this->assertDatabaseHas('ticket_reservations', [
            'session_token' => $token,
            'status'        => 'purchased',
            'user_id'       => $user->id // Ensure it's linked to the correct user
        ]);

        $this->assertEquals(8, $ticketType->fresh()->remaining_inventory);
    }
}