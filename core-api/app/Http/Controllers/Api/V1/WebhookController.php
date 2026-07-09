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
}

