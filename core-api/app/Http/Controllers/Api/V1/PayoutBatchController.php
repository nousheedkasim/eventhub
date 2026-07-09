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
        return response()->json($this->payoutBatchService->getAll());
    }

    public function store(StorePayoutBatchRequest $request)
    {
        return response()->json(
            $this->payoutBatchService->create($request->validated()),
            201
        );
    }

    public function show(PayoutBatch $payoutBatch)
    {
        return response()->json($payoutBatch);
    }

    public function update(UpdatePayoutBatchRequest $request, PayoutBatch $payoutBatch)
    {
        return response()->json(
            $this->payoutBatchService->update($payoutBatch->id, $request->validated())
        );
    }

    public function destroy(PayoutBatch $payoutBatch)
    {
        $this->payoutBatchService->delete($payoutBatch->id);

        return response()->json([
            'message' => 'Payout batch deleted successfully',
        ]);
    }
}

