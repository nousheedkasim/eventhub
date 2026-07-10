<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Event;
use App\Models\TicketType;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class OrderServiceTest extends TestCase
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

        // Create Attendee
        $this->attendee = User::create([
            'name' => 'Attendee User',
            'email' => 'attendee@example.com',
            'password' => bcrypt('password123'),
            'type' => 'user',
        ]);

        // Create Vendor
        $this->vendor = Vendor::create([
            'company_name' => 'Vendor Inc.',
            'contact_person' => 'Vendor Manager',
            'email' => 'vendor@example.com',
            'is_active' => true,
        ]);

        // Create Event (in 20 days so early-bird applies)
        $this->event = new Event([
            'title' => 'Sample Conference',
            'description' => 'A conference test event.',
            'location' => 'Dubai WTC',
            'event_date' => now()->addDays(20),
        ]);
        $this->event->vendor_id = $this->vendor->id;
        $this->event->save();

        // Create Ticket Type
        $this->ticketType = TicketType::create([
            'event_id' => $this->event->id,
            'type' => 'General Admission',
            'price' => 100.00,
            'inventory' => 50,
            'sold_count' => 0,
            'available_from' => now()->subDays(1),
            'available_until' => now()->addDays(5),
            'is_active' => true,
        ]);
    }

    /**
     * Test successful ticket hold creation and early-bird discount calculation.
     */
    public function test_ticket_hold_creation(): void
    {
        $order = $this->orderService->create([
            'attendee_id' => $this->attendee->id,
            'items' => [
                [
                    'ticket_type_id' => $this->ticketType->id,
                    'qty' => 2,
                ]
            ]
        ]);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals('held', $order->status);
        
        // 2 tickets * $100 = $200. Early Bird gives 10% discount: $180.
        $this->assertEquals(180.00, (float) $order->total_amount);

        // Verify ticket type inventory decremented (sold_count incremented)
        $this->ticketType->refresh();
        $this->assertEquals(2, $this->ticketType->sold_count);

        // Verify audit log
        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'from_status' => null,
            'to_status' => 'held',
        ]);
    }

    /**
     * Test group bundle pricing discount (buy 4 or more gets 20% extra discount).
     */
    public function test_group_bundle_pricing_discount(): void
    {
        $order = $this->orderService->create([
            'attendee_id' => $this->attendee->id,
            'items' => [
                [
                    'ticket_type_id' => $this->ticketType->id,
                    'qty' => 4,
                ]
            ]
        ]);

        // 4 tickets * $100 = $400.
        // Early Bird 10% + Group Bundle 20% = 30% discount.
        // Final: $400 * 0.70 = $280.
        $this->assertEquals(280.00, (float) $order->total_amount);

        $this->ticketType->refresh();
        $this->assertEquals(4, $this->ticketType->sold_count);
    }

    /**
     * Test that expired holds release inventory and mark the order as expired.
     */
    public function test_hold_expiry_releases_inventory(): void
    {
        $order = $this->orderService->create([
            'attendee_id' => $this->attendee->id,
            'items' => [
                [
                    'ticket_type_id' => $this->ticketType->id,
                    'qty' => 2,
                ]
            ]
        ]);

        $this->ticketType->refresh();
        $this->assertEquals(2, $this->ticketType->sold_count);

        // Force order expiration
        $order->hold_expires_at = now()->subMinutes(16);
        $order->save();

        // Run cleanup
        $cleanedCount = $this->orderService->cleanupExpiredHolds();
        $this->assertEquals(1, $cleanedCount);

        $order->refresh();
        $this->assertEquals('expired', $order->status);

        // Inventory should be reclaimed
        $this->ticketType->refresh();
        $this->assertEquals(0, $this->ticketType->sold_count);

        // Verify expiration event log
        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'from_status' => 'held',
            'to_status' => 'expired',
        ]);
    }

    /**
     * Test that ordering more tickets than available fails.
     */
    public function test_ticket_inventory_capacity_limits(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Insufficient inventory');

        $this->orderService->create([
            'attendee_id' => $this->attendee->id,
            'items' => [
                [
                    'ticket_type_id' => $this->ticketType->id,
                    'qty' => 51,
                ]
            ]
        ]);
    }

    /**
     * Test distributed lock prevention under concurrent purchase requests.
     */
    public function test_concurrent_purchase_attempts(): void
    {
        $lockKey = "ticket_type_lock_" . $this->ticketType->id;
        $lock = Cache::lock($lockKey, 10);
        
        // 1. Manually acquire the lock to simulate another request holding it
        $this->assertTrue($lock->get());

        // 2. Attempt check out should fail immediately due to lock exclusion
        try {
            $this->orderService->create([
                'attendee_id' => $this->attendee->id,
                'items' => [
                    [
                        'ticket_type_id' => $this->ticketType->id,
                        'qty' => 1,
                    ]
                ]
            ]);
            $this->fail("Expected RuntimeException was not thrown.");
        } catch (\RuntimeException $e) {
            $this->assertEquals(409, $e->getCode());
            $this->assertStringContainsString("Could not acquire lock", $e->getMessage());
        }

        // 3. Release the lock
        $lock->release();

        // 4. Try checkout again, should now succeed cleanly
        $order = $this->orderService->create([
            'attendee_id' => $this->attendee->id,
            'items' => [
                [
                    'ticket_type_id' => $this->ticketType->id,
                    'qty' => 1,
                ]
            ]
        ]);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals('held', $order->status);
    }
}
