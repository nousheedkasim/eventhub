<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\OrderEvent;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
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

        DB::transaction(function () use ($payment, $status) {
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

            Log::info("STUB: Triggering confirmation notification for Order #{$order->id} (Status: {$newOrderStatus}).");
        });

        return ['order_id' => $orderId, 'status' => $status];
    }
}
