<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePayoutBatchRequest;
use App\Http\Requests\UpdatePayoutBatchRequest;
use App\Models\PayoutBatch;
use App\Services\PayoutBatchService;

class PayoutBatchController extends Controller
{
    public function __construct(
        private PayoutBatchService $payoutBatchService
    ) {}

    public function index()
    {
        return response()->json(['success' => true, 'data' => $this->payoutBatchService->getAll(), 'message' => 'Retrieved successfully']);
    }

    public function store(StorePayoutBatchRequest $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->payoutBatchService->create($request->validated()),
            'message' => 'Created successfully',
        ], 201);
    }

    public function show(PayoutBatch $payoutBatch)
    {
        return response()->json(['success' => true, 'data' => $payoutBatch, 'message' => 'Retrieved successfully']);
    }

    public function update(UpdatePayoutBatchRequest $request, PayoutBatch $payoutBatch)
    {
        return response()->json([
            'success' => true,
            'data' => $this->payoutBatchService->update($payoutBatch->id, $request->validated()),
            'message' => 'Updated successfully',
        ]);
    }

    public function destroy(PayoutBatch $payoutBatch)
    {
        $this->payoutBatchService->delete($payoutBatch->id);

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Deleted successfully',
        ]);
    }
}

