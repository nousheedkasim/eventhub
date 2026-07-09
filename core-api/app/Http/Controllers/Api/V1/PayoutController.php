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

    public function index()
    {
        return response()->json($this->payoutService->getAll());
    }

    public function store(StorePayoutRequest $request)
    {
        return response()->json(
            $this->payoutService->create($request->validated()),
            201
        );
    }

    public function show(Payout $payout)
    {
        return response()->json($payout);
    }

    public function update(UpdatePayoutRequest $request, Payout $payout)
    {
        return response()->json(
            $this->payoutService->update($payout->id, $request->validated())
        );
    }

    public function destroy(Payout $payout)
    {
        $this->payoutService->delete($payout->id);

        return response()->json([
            'message' => 'Payout deleted successfully',
        ]);
    }
}

