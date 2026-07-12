<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderEvent;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\PayoutBatch;
use App\Models\Payout;
use App\Models\Dispute;
use App\Models\User;
use App\Models\TicketType;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $attendee = User::where('email', 'attendee@example.com')->first();
        $events = Event::all();

        // --- Order 1: Paid order for first event ---
        $event1 = $events->first();
        $gaTicket = TicketType::where('event_id', $event1->id)->where('type', 'General Admission')->first();
        $vipTicket = TicketType::where('event_id', $event1->id)->where('type', 'VIP')->first();

        $order1 = Order::create([
            'attendee_id' => $attendee->id,
            'status' => 'paid',
            'total_amount' => 25700,
            'hold_expires_at' => null,
        ]);

        OrderItem::create([
            'order_id' => $order1->id,
            'ticket_type_id' => $gaTicket->id,
            'qty' => 2,
            'price_at_purchase' => 8900,
        ]);

        OrderItem::create([
            'order_id' => $order1->id,
            'ticket_type_id' => $vipTicket->id,
            'qty' => 1,
            'price_at_purchase' => 7900,
        ]);

        $gaTicket->update(['sold_count' => $gaTicket->sold_count + 2]);
        $vipTicket->update(['sold_count' => $vipTicket->sold_count + 1]);

        OrderEvent::create([
            'order_id' => $order1->id,
            'from_status' => null,
            'to_status' => 'held',
            'payload' => ['message' => 'Order created via seeder.'],
        ]);

        OrderEvent::create([
            'order_id' => $order1->id,
            'from_status' => 'held',
            'to_status' => 'paid',
            'payload' => ['message' => 'Payment confirmed via seeder.'],
        ]);

        $payment1 = Payment::create([
            'order_id' => $order1->id,
            'gateway' => 'stripe',
            'status' => 'paid',
            'idempotency_key' => 'seeder-order-1-stripe',
            'gateway_reference' => 'sim_stripe_ref_001',
            'amount' => 25700,
            'currency' => 'USD',
            'paid_at' => Carbon::now()->subDays(5),
        ]);

        PaymentEvent::create([
            'payment_id' => $payment1->id,
            'from_status' => null,
            'to_status' => 'paid',
            'payload' => ['gateway' => 'stripe', 'reference' => 'sim_stripe_ref_001'],
        ]);

        // --- Order 2: Paid order for second event ---
        $event2 = $events->get(1);
        $gaTicket2 = TicketType::where('event_id', $event2->id)->where('type', 'General Admission')->first();

        $order2 = Order::create([
            'attendee_id' => $attendee->id,
            'status' => 'paid',
            'total_amount' => 8910,
            'hold_expires_at' => null,
        ]);

        OrderItem::create([
            'order_id' => $order2->id,
            'ticket_type_id' => $gaTicket2->id,
            'qty' => 1,
            'price_at_purchase' => 8910,
        ]);

        $gaTicket2->update(['sold_count' => $gaTicket2->sold_count + 1]);

        $payment2 = Payment::create([
            'order_id' => $order2->id,
            'gateway' => 'paypal',
            'status' => 'paid',
            'idempotency_key' => 'seeder-order-2-paypal',
            'gateway_reference' => 'sim_paypal_ref_001',
            'amount' => 8910,
            'currency' => 'USD',
            'paid_at' => Carbon::now()->subDays(3),
        ]);

        PaymentEvent::create([
            'payment_id' => $payment2->id,
            'from_status' => null,
            'to_status' => 'paid',
            'payload' => ['gateway' => 'paypal', 'reference' => 'sim_paypal_ref_001'],
        ]);

        // --- Order 3: Refunded order ---
        $order3 = Order::create([
            'attendee_id' => $attendee->id,
            'status' => 'refunded',
            'total_amount' => 7900,
            'hold_expires_at' => null,
        ]);

        OrderItem::create([
            'order_id' => $order3->id,
            'ticket_type_id' => $vipTicket->id,
            'qty' => 1,
            'price_at_purchase' => 7900,
        ]);

        $payment3 = Payment::create([
            'order_id' => $order3->id,
            'gateway' => 'stripe',
            'status' => 'refunded',
            'idempotency_key' => 'seeder-order-3-refund',
            'gateway_reference' => 'sim_stripe_ref_003',
            'amount' => 7900,
            'currency' => 'USD',
            'paid_at' => Carbon::now()->subDays(10),
        ]);

        // --- Payout Batch & Payout ---
        $batch = PayoutBatch::create([
            'batch_reference' => 'BATCH-2026-07-001',
            'status' => 'completed',
            'total_payouts' => 1,
            'processed_count' => 1,
            'started_at' => Carbon::now()->subDays(2),
            'completed_at' => Carbon::now()->subDays(2),
        ]);

        $vendor = $event1->vendor;

        Payout::create([
            'vendor_id' => $vendor->id,
            'payout_batch_id' => $batch->id,
            'gross_amount' => 34610,
            'commission' => 3461,
            'amount' => 31149,
            'status' => 'paid',
            'transfer_reference' => 'payout_ref_001',
            'paid_at' => Carbon::now()->subDays(2),
        ]);

        // --- Dispute on order 3 ---
        Dispute::create([
            'order_id' => $order3->id,
            'status' => 'resolved',
            'reason' => 'Requested refund due to schedule conflict.',
            'resolution' => 'Full refund issued. Event was more than 48 hours away.',
            'resolved_at' => Carbon::now()->subDays(9),
        ]);

        $this->command->info('Orders, payments, payouts, and disputes seeded successfully.');
    }
}
