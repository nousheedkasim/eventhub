<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRefundRequest;
use App\Http\Requests\UpdateRefundRequest;
use App\Models\Refund;
use App\Services\RefundService;

class RefundController extends Controller
{
    public function __construct(
        private RefundService $refundService
    ) {}

    public function index()
    {
        return response()->json(['success' => true, 'data' => $this->refundService->getAll(), 'message' => 'Retrieved successfully']);
    }

    public function store(StoreRefundRequest $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->refundService->create($request->validated()),
            'message' => 'Created successfully',
        ], 201);
    }

    public function show(Refund $refund)
    {
        return response()->json(['success' => true, 'data' => $refund, 'message' => 'Retrieved successfully']);
    }

    public function update(UpdateRefundRequest $request, Refund $refund)
    {
        return response()->json([
            'success' => true,
            'data' => $this->refundService->update($refund->id, $request->validated()),
            'message' => 'Updated successfully',
        ]);
    }

    public function destroy(Refund $refund)
    {
        $this->refundService->delete($refund->id);

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Deleted successfully',
        ]);
    }
}

