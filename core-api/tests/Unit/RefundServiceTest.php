<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Event;
use App\Models\TicketType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RefundServiceTest extends TestCase
{
    use RefreshDatabase;

    private RefundService $refundService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->refundService = app(RefundService::class);
    }

    private function createTestData(array $paymentOverrides = []): array
    {
        $defaults = ['status' => 'paid', 'amount' => 10000];
        $paymentAttrs = array_merge($defaults, $paymentOverrides);

        $user = User::create([
            'name' => 'Test Attendee',
            'email' => 'attendee@test.com',
            'password' => 'password',
            'type' => 'attendee',
        ]);

        $vendor = Vendor::create([
            'company_name' => 'Test Vendor',
            'contact_person' => 'Jane Doe',
            'email' => 'vendor@test.com',
            'kyc_status' => 'verified',
        ]);

        $event = Event::create([
            'vendor_id' => $vendor->id,
            'title' => 'Test Event',
            'location' => 'Test Location',
            'event_date' => now()->addDays(30),
        ]);

        $ticketType = TicketType::create([
            'event_id' => $event->id,
            'type' => 'General Admission',
            'price' => 5000,
            'inventory' => 100,
            'sold_count' => 2,
            'available_from' => now(),
            'available_until' => now()->addDays(30),
        ]);

        $order = Order::create([
            'attendee_id' => $user->id,
            'status' => 'paid',
            'total_amount' => $paymentAttrs['amount'],
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'ticket_type_id' => $ticketType->id,
            'qty' => 2,
            'price_at_purchase' => 5000,
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => 'stripe',
            'status' => $paymentAttrs['status'],
            'idempotency_key' => 'pay_key_' . $order->id,
            'gateway_reference' => 'ref_12345',
            'amount' => $paymentAttrs['amount'],
            'currency' => 'USD',
        ]);

        return compact('user', 'vendor', 'event', 'ticketType', 'order', 'payment');
    }

    public function test_cannot_refund_non_paid_payment(): void
    {
        $data = $this->createTestData(['status' => 'pending', 'amount' => 10000]);

        Http::fake();

        try {
            $this->refundService->create([
                'payment_id' => $data['payment']->id,
                'amount' => 5000,
                'reason' => 'Test refund',
            ]);
            $this->fail('Expected HttpException was not thrown.');
        } catch (HttpException $e) {
            $this->assertEquals(422, $e->getStatusCode());
            $this->assertEquals('Refunds can only be processed for successful payments.', $e->getMessage());
        }
    }

    public function test_cannot_refund_exceeding_original_amount(): void
    {
        $data = $this->createTestData(['status' => 'paid', 'amount' => 10000]);

        Http::fake();

        try {
            $this->refundService->create([
                'payment_id' => $data['payment']->id,
                'amount' => 15000,
                'reason' => 'Over refund',
            ]);
            $this->fail('Expected HttpException was not thrown.');
        } catch (HttpException $e) {
            $this->assertEquals(422, $e->getStatusCode());
            $this->assertEquals('Requested refund amount exceeds the original payment value.', $e->getMessage());
        }
    }

    public function test_cannot_refund_already_fully_refunded_payment(): void
    {
        $data = $this->createTestData(['status' => 'paid', 'amount' => 10000]);

        Refund::create([
            'payment_id' => $data['payment']->id,
            'amount' => 10000,
            'policy_applied' => 'Full refund',
            'status' => 'completed',
            'reason' => 'Previous refund',
        ]);

        Http::fake();

        try {
            $this->refundService->create([
                'payment_id' => $data['payment']->id,
                'amount' => 5000,
                'reason' => 'Try again',
            ]);
            $this->fail('Expected HttpException was not thrown.');
        } catch (HttpException $e) {
            $this->assertEquals(422, $e->getStatusCode());
            $this->assertEquals('Requested refund amount exceeds the original payment value.', $e->getMessage());
        }
    }
}
