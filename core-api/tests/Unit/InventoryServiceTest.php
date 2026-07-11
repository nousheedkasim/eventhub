<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Event;
use App\Models\TicketType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderEvent;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderService $orderService;
    private User $attendee;
    private Vendor $vendor;
    private Event $event;
    private TicketType $ticketType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderService = $this->app->make(OrderService::class);

        $this->attendee = User::create([
            'name' => 'Test Attendee',
            'email' => 'attendee@test.com',
            'password' => bcrypt('password'),
            'type' => 'user',
        ]);

        $this->vendor = Vendor::create([
            'company_name' => 'Test Vendor',
            'contact_person' => 'Test Person',
            'email' => 'vendor@test.com',
            'is_active' => true,
        ]);

        $this->event = new Event([
            'title' => 'Test Event',
            'description' => 'Test description',
            'location' => 'Test Location',
            'event_date' => now()->addDays(20),
        ]);
        $this->event->vendor_id = $this->vendor->id;
        $this->event->save();

        $this->ticketType = TicketType::create([
            'event_id' => $this->event->id,
            'type' => 'General Admission',
            'price' => 10000,
            'inventory' => 10,
            'sold_count' => 0,
            'available_from' => now()->subDays(1),
            'available_until' => now()->addDays(5),
            'is_active' => true,
        ]);
    }

    public function test_inventory_decrements_on_order_creation(): void
    {
        $this->orderService->create([
            'attendee_id' => $this->attendee->id,
            'items' => [
                ['ticket_type_id' => $this->ticketType->id, 'qty' => 3],
            ],
        ]);

        $this->ticketType->refresh();
        $this->assertEquals(3, $this->ticketType->sold_count);
        $this->assertEquals(7, $this->ticketType->inventory - $this->ticketType->sold_count);
    }

    public function test_inventory_restores_after_hold_expiry(): void
    {
        $order = $this->orderService->create([
            'attendee_id' => $this->attendee->id,
            'items' => [
                ['ticket_type_id' => $this->ticketType->id, 'qty' => 4],
            ],
        ]);

        $this->ticketType->refresh();
        $this->assertEquals(4, $this->ticketType->sold_count);

        // Expire the hold
        $order->hold_expires_at = now()->subMinutes(16);
        $order->save();

        $this->orderService->cleanupExpiredHolds();

        $this->ticketType->refresh();
        $this->assertEquals(0, $this->ticketType->sold_count);
    }

    public function test_inventory_partial_restores_on_partial_order_expiry(): void
    {
        // Create two orders
        $order1 = $this->orderService->create([
            'attendee_id' => $this->attendee->id,
            'items' => [
                ['ticket_type_id' => $this->ticketType->id, 'qty' => 3],
            ],
        ]);

        $this->ticketType->refresh();
        $this->assertEquals(3, $this->ticketType->sold_count);

        // Expire only the first order
        $order1->hold_expires_at = now()->subMinutes(16);
        $order1->save();

        $this->orderService->cleanupExpiredHolds();

        $this->ticketType->refresh();
        $this->assertEquals(0, $this->ticketType->sold_count);
    }

    public function test_cannot_order_more_than_remaining_inventory(): void
    {
        $this->expectException(\RuntimeException::class);

        // Try to order 11 tickets when only 10 are available
        $this->orderService->create([
            'attendee_id' => $this->attendee->id,
            'items' => [
                ['ticket_type_id' => $this->ticketType->id, 'qty' => 11],
            ],
        ]);
    }

    public function test_exact_inventory_order_succeeds(): void
    {
        $order = $this->orderService->create([
            'attendee_id' => $this->attendee->id,
            'items' => [
                ['ticket_type_id' => $this->ticketType->id, 'qty' => 10],
            ],
        ]);

        $this->assertInstanceOf(Order::class, $order);

        $this->ticketType->refresh();
        $this->assertEquals(10, $this->ticketType->sold_count);
        $this->assertEquals(0, $this->ticketType->inventory - $this->ticketType->sold_count);
    }

    public function test_inactive_ticket_type_rejects_order(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->ticketType->update(['is_active' => false]);

        $this->orderService->create([
            'attendee_id' => $this->attendee->id,
            'items' => [
                ['ticket_type_id' => $this->ticketType->id, 'qty' => 1],
            ],
        ]);
    }

    public function test_outside_availability_window_rejects_order(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->ticketType->update([
            'available_from' => now()->addDays(10),
            'available_until' => now()->addDays(20),
        ]);

        $this->orderService->create([
            'attendee_id' => $this->attendee->id,
            'items' => [
                ['ticket_type_id' => $this->ticketType->id, 'qty' => 1],
            ],
        ]);
    }

    public function test_multiple_ticket_types_shared_inventory(): void
    {
        // Create a second ticket type with limited inventory
        $vipTicket = TicketType::create([
            'event_id' => $this->event->id,
            'type' => 'VIP',
            'price' => 20000,
            'inventory' => 5,
            'sold_count' => 0,
            'available_from' => now()->subDays(1),
            'available_until' => now()->addDays(5),
            'is_active' => true,
        ]);

        // Order both types
        $order = $this->orderService->create([
            'attendee_id' => $this->attendee->id,
            'items' => [
                ['ticket_type_id' => $this->ticketType->id, 'qty' => 2],
                ['ticket_type_id' => $vipTicket->id, 'qty' => 2],
            ],
        ]);

        $this->ticketType->refresh();
        $vipTicket->refresh();

        $this->assertEquals(2, $this->ticketType->sold_count);
        $this->assertEquals(2, $vipTicket->sold_count);
    }

    public function test_audit_log_recorded_on_inventory_change(): void
    {
        $order = $this->orderService->create([
            'attendee_id' => $this->attendee->id,
            'items' => [
                ['ticket_type_id' => $this->ticketType->id, 'qty' => 2],
            ],
        ]);

        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'to_status' => 'held',
        ]);
    }

    public function test_audit_log_recorded_on_hold_expiry(): void
    {
        $order = $this->orderService->create([
            'attendee_id' => $this->attendee->id,
            'items' => [
                ['ticket_type_id' => $this->ticketType->id, 'qty' => 1],
            ],
        ]);

        $order->hold_expires_at = now()->subMinutes(16);
        $order->save();

        $this->orderService->cleanupExpiredHolds();

        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'from_status' => 'held',
            'to_status' => 'expired',
        ]);
    }
}
