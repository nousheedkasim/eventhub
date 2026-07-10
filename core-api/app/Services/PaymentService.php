<?php

namespace App\Services;

use App\Repositories\PaymentRepository;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\Order;
use App\Models\OrderEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct(
        private PaymentRepository $repository
    ) {}

    public function getAll()
    {
        return $this->repository->all();
    }

    /**
     * Create a payment record and call the payment microservice
     */
    public function create(array $data)
    {
        $orderId = $data['order_id'];
        $gateway = $data['gateway'];
        $idempotencyKey = $data['idempotency_key'] ?? 'pay_key_' . $orderId . '_' . uniqid();

        // 1. Fetch Order and verify status
        $order = Order::findOrFail($orderId);
        if ($order->status !== 'held') {
            throw new \RuntimeException("Payment can only be processed for orders that are currently held.", 422);
        }

        // 2. Check if a payment for this order already exists
        $existingPayment = Payment::where('order_id', $orderId)->first();
        if ($existingPayment) {
            if ($existingPayment->status === 'paid') {
                return $existingPayment;
            }
            // If it is pending, we can return it to prevent duplicate submission or keep going
        }

        // 3. Create the payment record in pending status
        return DB::transaction(function () use ($order, $gateway, $idempotencyKey) {
            $payment = Payment::create([
                'order_id' => $order->id,
                'gateway' => $gateway,
                'status' => 'pending',
                'idempotency_key' => $idempotencyKey,
                'amount' => $order->total_amount,
                'currency' => 'USD',
            ]);

            PaymentEvent::create([
                'payment_id' => $payment->id,
                'from_status' => null,
                'to_status' => 'pending',
                'payload' => [
                    'message' => 'Payment initialized.',
                    'idempotency_key' => $idempotencyKey,
                ],
            ]);

            // 4. Dispatch the HTTP request to the payment microservice
            $url = config('services.payment.url') . '/api/payments';
            $secret = config('services.payment.secret');

            // Find event date from order items
            $eventDate = $order->items->first()?->ticketType?->event?->event_date;
            $eventDateString = $eventDate ? $eventDate->toDateTimeString() : now()->addDays(7)->toDateTimeString();

            try {
                $response = Http::withToken($secret)
                    ->withHeaders([
                        'Idempotency-Key' => $idempotencyKey,
                    ])
                    ->post($url, [
                        'order_id' => $order->id,
                        'amount' => $payment->amount,
                        'currency' => $payment->currency,
                        'gateway' => $payment->gateway,
                        'event_date' => $eventDateString,
                    ]);

                if ($response->failed()) {
                    throw new \RuntimeException("Payment service responded with error: " . $response->body(), $response->status());
                }

                $responseData = $response->json();

                // Update payment gateway reference returned by microservice
                $payment->gateway_reference = $responseData['payment_reference'] ?? null;
                $payment->save();

                PaymentEvent::create([
                    'payment_id' => $payment->id,
                    'from_status' => 'pending',
                    'to_status' => 'pending',
                    'payload' => [
                        'message' => 'Sent transaction request to payment service. Awaiting webhook status callback.',
                        'response' => $responseData,
                    ],
                ]);

                return $payment;

            } catch (\Exception $e) {
                // Fail the payment record since call could not be completed
                $payment->status = 'failed';
                $payment->save();

                PaymentEvent::create([
                    'payment_id' => $payment->id,
                    'from_status' => 'pending',
                    'to_status' => 'failed',
                    'payload' => [
                        'error' => $e->getMessage(),
                        'message' => 'Failed to reach payment service.',
                    ],
                ]);

                Log::error("Failed to initiate payment for Order #{$order->id}: " . $e->getMessage());
                throw new \RuntimeException("Payment microservice call failed: " . $e->getMessage(), 500);
            }
        });
    }

    public function find($id)
    {
        return $this->repository->find($id);
    }

    public function update($id, array $data)
    {
        // Handle audit trail event when payment status is updated (e.g. from webhook callback)
        $payment = Payment::findOrFail($id);
        $oldStatus = $payment->status;
        $newStatus = $data['status'] ?? $oldStatus;

        return DB::transaction(function () use ($id, $data, $payment, $oldStatus, $newStatus) {
            $updatedPayment = $this->repository->update($id, $data);

            if ($oldStatus !== $newStatus) {
                PaymentEvent::create([
                    'payment_id' => $payment->id,
                    'from_status' => $oldStatus,
                    'to_status' => $newStatus,
                    'payload' => [
                        'message' => 'Payment status updated.',
                        'data' => $data,
                    ],
                ]);
            }

            return $updatedPayment;
        });
    }

    public function delete($id)
    {
        return $this->repository->delete($id);
    }
}
