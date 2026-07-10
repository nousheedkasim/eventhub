<?php

namespace App\Services;

use App\Repositories\RefundRepository;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\RefundEvent;
use App\Models\PaymentEvent;
use App\Models\Order;
use App\Models\OrderEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RefundService
{
    public function __construct(
        private RefundRepository $repository
    ) {}

    public function getAll()
    {
        return $this->repository->all();
    }

    /**
     * Create a refund and initiate refund call to the payment microservice
     */
    public function create(array $data)
    {
        $paymentId = $data['payment_id'];
        $amount = (float) $data['amount'];
        $reason = $data['reason'] ?? null;

        // 1. Fetch Payment and related Order details
        $payment = Payment::with('order.items.ticketType.event')->findOrFail($paymentId);

        if ($payment->status !== 'paid') {
            throw new \RuntimeException("Refunds can only be processed for successful payments.", 422);
        }

        // Calculate already refunded amount to support partial refunds
        $alreadyRefunded = Refund::where('payment_id', $paymentId)
            ->whereIn('status', ['approved', 'completed'])
            ->sum('amount');

        if (($alreadyRefunded + $amount) > (float) $payment->amount) {
            throw new \RuntimeException("Requested refund amount exceeds the original payment value.", 422);
        }

        return DB::transaction(function () use ($payment, $amount, $reason) {
            // 2. Create Refund in 'pending' status
            $refund = Refund::create([
                'payment_id' => $payment->id,
                'amount' => $amount,
                'policy_applied' => 'pending',
                'status' => 'pending',
                'reason' => $reason,
            ]);

            RefundEvent::create([
                'refund_id' => $refund->id,
                'from_status' => null,
                'to_status' => 'pending',
                'payload' => [
                    'message' => 'Refund request initiated.',
                    'amount' => $amount,
                ],
            ]);

            // 3. Dispatch synchronous HTTP request to the payment microservice
            $url = config('services.payment.url') . '/api/payments/refund';
            $secret = config('services.payment.secret');

            // Determine the event date (fallback to 7 days out if not resolved)
            $eventDate = $payment->order->items->first()?->ticketType?->event?->event_date;
            $eventDateString = $eventDate ? $eventDate->toDateTimeString() : now()->addDays(7)->toDateTimeString();

            try {
                $response = Http::withToken($secret)->post($url, [
                    'payment_reference' => $payment->gateway_reference,
                    'amount' => $amount,
                    'event_date' => $eventDateString,
                ]);

                if ($response->failed()) {
                    throw new \RuntimeException("Payment microservice refund call failed: " . $response->body(), $response->status());
                }

                $responseData = $response->json();

                // 4. Update Refund status upon success
                $refund->status = 'completed';
                $refund->policy_applied = $responseData['policy_applied'] ?? 'Default policy';
                $refund->refunded_at = now();
                $refund->save();

                RefundEvent::create([
                    'refund_id' => $refund->id,
                    'from_status' => 'pending',
                    'to_status' => 'completed',
                    'payload' => [
                        'message' => 'Refund captured by payment gateway.',
                        'response' => $responseData,
                    ],
                ]);

                // 5. Update Payment Status to refunded (or keep track if partial)
                $payment->status = 'refunded';
                $payment->save();

                PaymentEvent::create([
                    'payment_id' => $payment->id,
                    'from_status' => 'paid',
                    'to_status' => 'refunded',
                    'payload' => [
                        'message' => 'Payment status updated to refunded.',
                        'refund_id' => $refund->id,
                    ],
                ]);

                // 6. Update Order Status to refunded
                $order = $payment->order;
                $oldOrderStatus = $order->status;
                $order->status = 'refunded';
                $order->save();

                OrderEvent::create([
                    'order_id' => $order->id,
                    'from_status' => $oldOrderStatus,
                    'to_status' => 'refunded',
                    'payload' => [
                        'message' => 'Order marked as refunded.',
                        'refund_id' => $refund->id,
                    ],
                ]);

                // 7. Release tickets back to inventory
                foreach ($order->items as $item) {
                    $ticketType = $item->ticketType;
                    if ($ticketType) {
                        $ticketType->sold_count = max(0, $ticketType->sold_count - $item->qty);
                        $ticketType->save();
                    }
                }

                return $refund;

            } catch (\Exception $e) {
                // Refund execution failed
                $refund->status = 'failed';
                $refund->save();

                RefundEvent::create([
                    'refund_id' => $refund->id,
                    'from_status' => 'pending',
                    'to_status' => 'failed',
                    'payload' => [
                        'error' => $e->getMessage(),
                        'message' => 'Refund failed at gateway.',
                    ],
                ]);

                Log::error("Failed to execute refund for Payment #{$payment->id}: " . $e->getMessage());
                throw new \RuntimeException("Refund execution failed: " . $e->getMessage(), 500);
            }
        });
    }

    public function find($id)
    {
        return $this->repository->find($id);
    }

    public function update($id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    public function delete($id)
    {
        return $this->repository->delete($id);
    }
}
