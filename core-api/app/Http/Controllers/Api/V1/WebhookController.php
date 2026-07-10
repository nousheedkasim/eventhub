<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWebhookRequest;
use App\Http\Requests\UpdateWebhookRequest;
use App\Models\Webhook;
use App\Services\WebhookService;

class WebhookController extends Controller
{
    public function __construct(
        private WebhookService $webhookService
    ) {}

    public function index()
    {
        return response()->json($this->webhookService->getAll());
    }

    public function store(StoreWebhookRequest $request)
    {
        return response()->json(
            $this->webhookService->create($request->validated()),
            201
        );
    }

    public function show(Webhook $webhook)
    {
        return response()->json($webhook);
    }

    public function update(UpdateWebhookRequest $request, Webhook $webhook)
    {
        return response()->json(
            $this->webhookService->update($webhook->id, $request->validated())
        );
    }

    public function destroy(Webhook $webhook)
    {
        $this->webhookService->delete($webhook->id);

        return response()->json([
            'message' => 'Webhook deleted successfully',
        ]);
    }

    /**
     * Handle payment status callbacks from the payment microservice
     */
    public function handlePaymentCallback(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer',
            'payment_reference' => 'required|string',
            'status' => 'required|string|in:paid,failed',
            'amount' => 'required|numeric',
        ]);

        $orderId = $request->input('order_id');
        $paymentReference = $request->input('payment_reference');
        $status = $request->input('status');

        $payment = \App\Models\Payment::where('order_id', $orderId)
            ->where('gateway_reference', $paymentReference)
            ->firstOrFail();

        \Illuminate\Support\Facades\DB::transaction(function () use ($payment, $status) {
            $oldPaymentStatus = $payment->status;
            $newPaymentStatus = $status; // 'paid' or 'failed'

            // 1. Update payment status
            $payment->status = $newPaymentStatus;
            if ($newPaymentStatus === 'paid') {
                $payment->paid_at = now();
            }
            $payment->save();

            \App\Models\PaymentEvent::create([
                'payment_id' => $payment->id,
                'from_status' => $oldPaymentStatus,
                'to_status' => $newPaymentStatus,
                'payload' => [
                    'message' => 'Payment status updated via callback webhook.',
                    'gateway_reference' => $payment->gateway_reference,
                ],
            ]);

            // 2. Update Order status
            $order = $payment->order;
            $oldOrderStatus = $order->status;
            // The enum for paid orders in orders table is 'paid' (or we can support 'paid')
            $newOrderStatus = ($newPaymentStatus === 'paid') ? 'paid' : 'cancelled';

            $order->status = $newOrderStatus;
            $order->hold_expires_at = null; // Clear hold expiration
            $order->save();

            \App\Models\OrderEvent::create([
                'order_id' => $order->id,
                'from_status' => $oldOrderStatus,
                'to_status' => $newOrderStatus,
                'payload' => [
                    'message' => "Order updated to {$newOrderStatus} due to payment callback.",
                    'payment_id' => $payment->id,
                ],
            ]);

            // 3. Release tickets back to inventory on failure
            if ($newPaymentStatus === 'failed') {
                foreach ($order->items as $item) {
                    $ticketType = $item->ticketType;
                    if ($ticketType) {
                        $ticketType->sold_count = max(0, $ticketType->sold_count - $item->qty);
                        $ticketType->save();
                    }
                }
            }

            // 4. Stub notification call for Day 4
            \Illuminate\Support\Facades\Log::info("STUB: Triggering confirmation notification for Order #{$order->id} (Status: {$newOrderStatus}).");
        });

        return response()->json([
            'success' => true,
            'message' => 'Webhook processed successfully.',
        ]);
    }
}

