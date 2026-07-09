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
        return response()->json($this->refundService->getAll());
    }

    public function store(StoreRefundRequest $request)
    {
        return response()->json(
            $this->refundService->create($request->validated()),
            201
        );
    }

    public function show(Refund $refund)
    {
        return response()->json($refund);
    }

    public function update(UpdateRefundRequest $request, Refund $refund)
    {
        return response()->json(
            $this->refundService->update($refund->id, $request->validated())
        );
    }

    public function destroy(Refund $refund)
    {
        $this->refundService->delete($refund->id);

        return response()->json([
            'message' => 'Refund deleted successfully',
        ]);
    }
}

