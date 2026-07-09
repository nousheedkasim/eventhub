<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Models\Payment;

use App\Services\PaymentService;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    public function index()
    {
        return response()->json($this->paymentService->getAll());
    }

    public function store(StorePaymentRequest $request)
    {
        return response()->json(
            $this->paymentService->create($request->validated()),
            201
        );
    }

    public function show(Payment $payment)
    {
        return response()->json($payment);
    }

    public function update(UpdatePaymentRequest $request, Payment $payment)
    {
        return response()->json(
            $this->paymentService->update($payment->id, $request->validated())
        );
    }

    public function destroy(Payment $payment)
    {
        $this->paymentService->delete($payment->id);

        return response()->json([
            'message' => 'Payment deleted successfully',
        ]);
    }
}

