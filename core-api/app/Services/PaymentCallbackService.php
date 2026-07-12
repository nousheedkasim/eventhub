<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\OrderEvent;
use App\Models\Webhook;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentCallbackService
{
    public function handleCallback(array $data): array
    {
        $orderId = $data['order_id'];
        $paymentReference = $data['payment_reference'];
        $status = $data['status'];

        $payment = Payment::where('order_id', $orderId)
            ->where('gateway_reference', $paymentReference)
            ->firstOrFail();

        $newPaymentStatus = null;

        DB::transaction(function () use ($payment, $status, &$newPaymentStatus) {
            $oldPaymentStatus = $payment->status;
            $newPaymentStatus = $status;

            $payment->status = $newPaymentStatus;
            if ($newPaymentStatus === 'paid') {
                $payment->paid_at = now();
            }
            $payment->save();

            PaymentEvent::create([
                'payment_id' => $payment->id,
                'from_status' => $oldPaymentStatus,
                'to_status' => $newPaymentStatus,
                'payload' => [
                    'message' => 'Payment status updated via callback webhook.',
                    'gateway_reference' => $payment->gateway_reference,
                ],
            ]);

            $order = $payment->order;
            $oldOrderStatus = $order->status;
            $newOrderStatus = ($newPaymentStatus === 'paid') ? 'paid' : 'cancelled';

            $order->status = $newOrderStatus;
            $order->hold_expires_at = null;
            $order->save();

            OrderEvent::create([
                'order_id' => $order->id,
                'from_status' => $oldOrderStatus,
                'to_status' => $newOrderStatus,
                'payload' => [
                    'message' => "Order updated to {$newOrderStatus} due to payment callback.",
                    'payment_id' => $payment->id,
                ],
            ]);

            if ($newPaymentStatus === 'failed') {
                foreach ($order->items as $item) {
                    $ticketType = $item->ticketType;
                    if ($ticketType) {
                        $ticketType->sold_count = max(0, $ticketType->sold_count - $item->qty);
                        $ticketType->save();
                    }
                }
            }
        });

        if ($newPaymentStatus === 'paid') {
            $this->dispatchNotifications($orderId);
        }

        return ['order_id' => $orderId, 'status' => $status];
    }

    private function dispatchNotifications(int $orderId): void
    {
        $notificationUrl = config('services.notification.url', 'http://localhost:3002');
        $notificationSecret = config('services.notification.secret', 'secure_shared_secret');

        $order = \App\Models\Order::with([
            'attendee',
            'items.ticketType.event.vendor',
        ])->findOrFail($orderId);

        $attendeeEmail = $order->attendee->email ?? null;

        if ($attendeeEmail) {
            try {
                Http::withHeaders([
                    'X-Internal-Secret' => $notificationSecret,
                ])->post($notificationUrl . '/api/notifications/email', [
                    'type' => 'order_confirmation',
                    'data' => [
                        'attendee_email' => $attendeeEmail,
                        'order_id' => $order->id,
                    ],
                ]);
                Log::info("Order confirmation email dispatched for Order #{$order->id} to {$attendeeEmail}");
            } catch (\Exception $e) {
                Log::error("Failed to dispatch order confirmation email for Order #{$order->id}: " . $e->getMessage());
            }
        }

        $vendorWebhookUrl = null;
        $vendorId = null;

        foreach ($order->items as $item) {
            $event = $item->ticketType?->event;
            if ($event && $event->vendor) {
                $vendorId = $event->vendor_id;
                $webhook = Webhook::where('vendor_id', $vendorId)
                    ->where('active', true)
                    ->first();
                if ($webhook) {
                    $vendorWebhookUrl = $webhook->url;
                    break;
                }
            }
        }

        if ($vendorWebhookUrl && $vendorId) {
            try {
                Http::withHeaders([
                    'X-Internal-Secret' => $notificationSecret,
                ])->post($notificationUrl . '/api/notifications/webhook', [
                    'type' => 'order_webhook',
                    'data' => [
                        'order_id' => $order->id,
                        'vendor_id' => $vendorId,
                        'total_amount' => $order->total_amount,
                        'currency' => 'usd',
                        'status' => $order->status,
                        'created_at' => $order->created_at->toIso8601String(),
                        'items' => $order->items->map(fn ($item) => [
                            'ticket_type_id' => $item->ticket_type_id,
                            'qty' => $item->qty,
                            'price_at_purchase' => $item->price_at_purchase,
                        ])->toArray(),
                        'vendor_webhook_url' => $vendorWebhookUrl,
                    ],
                ]);
                Log::info("Order webhook dispatched for Order #{$order->id} to vendor #{$vendorId}");
            } catch (\Exception $e) {
                Log::error("Failed to dispatch order webhook for Order #{$order->id}: " . $e->getMessage());
            }
        }
    }
}
