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
        return response()->json(['success' => true, 'data' => $this->paymentService->getAll(), 'message' => 'Retrieved successfully']);
    }

    public function store(StorePaymentRequest $request)
    {
        $payment = $this->paymentService->create($request->validated());
        return response()->json([
            'success' => true,
            'data' => $payment,
            'message' => 'Payment initiated successfully',
        ], 201);
    }

    public function show(Payment $payment)
    {
        return response()->json(['success' => true, 'data' => $payment, 'message' => 'Retrieved successfully']);
    }

    public function update(UpdatePaymentRequest $request, Payment $payment)
    {
        return response()->json([
            'success' => true,
            'data' => $this->paymentService->update($payment->id, $request->validated()),
            'message' => 'Updated successfully',
        ]);
    }

    public function destroy(Payment $payment)
    {
        $this->paymentService->delete($payment->id);

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Deleted successfully',
        ]);
    }
}

