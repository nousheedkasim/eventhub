<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWebhookRequest;
use App\Http\Requests\UpdateWebhookRequest;
use App\Models\Webhook;
use App\Services\WebhookService;
use App\Services\PaymentCallbackService;

class WebhookController extends Controller
{
    public function __construct(
        private WebhookService $webhookService,
        private PaymentCallbackService $paymentCallbackService
    ) {}

    public function index()
    {
        return response()->json(['success' => true, 'data' => $this->webhookService->getAll(), 'message' => 'Retrieved successfully']);
    }

    public function store(StoreWebhookRequest $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->webhookService->create($request->validated()),
            'message' => 'Created successfully',
        ], 201);
    }

    public function show(Webhook $webhook)
    {
        return response()->json(['success' => true, 'data' => $webhook, 'message' => 'Retrieved successfully']);
    }

    public function update(UpdateWebhookRequest $request, Webhook $webhook)
    {
        return response()->json([
            'success' => true,
            'data' => $this->webhookService->update($webhook->id, $request->validated()),
            'message' => 'Updated successfully',
        ]);
    }

    public function destroy(Webhook $webhook)
    {
        $this->webhookService->delete($webhook->id);

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Deleted successfully',
        ]);
    }

    public function registerVendorWebhook(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'events' => 'required|array',
            'events.*' => 'required|string|in:new_order,event_sold_out,payout_sent',
        ]);

        $user = $request->user();

        if ($user->type !== 'vendor') {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Only vendors can register webhooks',
            ], 403);
        }

        $vendor = $user->vendor;

        $webhook = Webhook::create([
            'vendor_id' => $vendor->id,
            'url' => $request->input('url'),
            'events' => $request->input('events'),
            'secret' => \Illuminate\Support\Str::random(32),
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'data' => $webhook,
            'message' => 'Webhook registered successfully',
        ], 201);
    }

    public function getVendorWebhooks(\Illuminate\Http\Request $request)
    {
        $user = $request->user();

        if ($user->type !== 'vendor') {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Only vendors can view their webhooks',
            ], 403);
        }

        $vendor = $user->vendor;
        $webhooks = Webhook::where('vendor_id', $vendor->id)->get();

        return response()->json([
            'success' => true,
            'data' => $webhooks,
            'message' => 'Webhooks retrieved successfully',
        ]);
    }

    public function handlePaymentCallback(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer',
            'payment_reference' => 'required|string',
            'status' => 'required|string|in:paid,failed',
            'amount' => 'required|numeric',
        ]);

        $result = $this->paymentCallbackService->handleCallback($request->only([
            'order_id', 'payment_reference', 'status',
        ]));

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => 'Webhook processed successfully.',
        ]);
    }
}
