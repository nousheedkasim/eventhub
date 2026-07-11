<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePayoutRequest;
use App\Http\Requests\UpdatePayoutRequest;
use App\Models\Payout;
use App\Services\PayoutService;

class PayoutController extends Controller
{
    public function __construct(
        private PayoutService $payoutService
    ) {}

    public function index(\Illuminate\Http\Request $request)
    {
        if ($request->has('vendor_id')) {
            $payouts = $this->payoutService->getByVendor($request->vendor_id);
            return response()->json(['success' => true, 'data' => $payouts, 'message' => 'Retrieved successfully']);
        }
        $payouts = $this->payoutService->getAll();
        return response()->json(['success' => true, 'data' => $payouts, 'message' => 'Retrieved successfully']);
    }

    public function store(StorePayoutRequest $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->payoutService->create($request->validated()),
            'message' => 'Created successfully',
        ], 201);
    }

    public function show(Payout $payout)
    {
        return response()->json(['success' => true, 'data' => $payout, 'message' => 'Retrieved successfully']);
    }

    public function update(UpdatePayoutRequest $request, Payout $payout)
    {
        return response()->json([
            'success' => true,
            'data' => $this->payoutService->update($payout->id, $request->validated()),
            'message' => 'Updated successfully',
        ]);
    }

    public function destroy(Payout $payout)
    {
        $this->payoutService->delete($payout->id);

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Deleted successfully',
        ]);
    }
}

