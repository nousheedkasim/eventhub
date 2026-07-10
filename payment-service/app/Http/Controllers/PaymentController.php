<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Jobs\SendWebhookCallbackJob;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PaymentController extends Controller
{
    /**
     * Store a payment and schedule the callback
     */
    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer',
            'amount' => 'required|numeric',
            'currency' => 'required|string|size:3',
            'gateway' => 'required|string|in:stripe,paypal',
            'event_date' => 'required|date',
        ]);

        $idempotencyKey = $request->header('Idempotency-Key');
        if (!$idempotencyKey) {
            return response()->json([
                'success' => false,
                'message' => 'Idempotency-Key header is required.'
            ], 400);
        }

        // Enforce idempotency: check if key is already processed
        $existingPayment = Payment::where('idempotency_key', $idempotencyKey)->first();
        if ($existingPayment) {
            Log::info("Idempotency match found for key '{$idempotencyKey}'. Short-circuiting repeat request.");
            return response()->json([
                'success' => true,
                'status' => 'pending',
                'payment_reference' => $existingPayment->payment_reference,
                'message' => 'Payment processing initiated (idempotency cached).'
            ]);
        }

        $orderId = $request->input('order_id');
        $amount = (float) $request->input('amount');
        $currency = $request->input('currency');
        $gateway = $request->input('gateway');
        $eventDate = $request->input('event_date');

        // Resolve and use gateway simulator class
        if ($gateway === 'stripe') {
            $simulator = new \App\Services\Gateways\StripeSimulator();
        } else {
            $simulator = new \App\Services\Gateways\PayPalSimulator();
        }

        $result = $simulator->charge($amount, $currency);
        $status = $result['status'];
        $paymentReference = $result['payment_reference'];

        // Save simulated payment request in SQLite
        $payment = Payment::create([
            'order_id' => $orderId,
            'amount' => $amount,
            'currency' => $currency,
            'gateway' => $gateway,
            'status' => $status,
            'idempotency_key' => $idempotencyKey,
            'payment_reference' => $paymentReference,
            'event_date' => Carbon::parse($eventDate),
            'refunded_amount' => 0.00,
        ]);

        // Dispatch background job with 3-second delay to callback main app
        SendWebhookCallbackJob::dispatch([
            'order_id' => $orderId,
            'payment_reference' => $paymentReference,
            'status' => $status,
            'amount' => $amount,
            'currency' => $currency,
        ])->delay(now()->addSeconds(3));

        return response()->json([
            'success' => true,
            'status' => 'pending',
            'payment_reference' => $paymentReference,
            'message' => 'Payment processing initiated.'
        ]);
    }

    /**
     * Refund a past payment applying time-based constraints
     */
    public function refund(Request $request)
    {
        $request->validate([
            'payment_reference' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'event_date' => 'required|date',
        ]);

        $paymentReference = $request->input('payment_reference');
        $refundAmount = (float) $request->input('amount');
        $eventDate = Carbon::parse($request->input('event_date'));

        $payment = Payment::where('payment_reference', $paymentReference)->firstOrFail();

        // Enforce the time-based refund policy:
        // - Full refund if requested > 48 hours before event
        // - 50% refund if 24-48 hours before event
        // - No refund if < 24 hours before event
        $now = now();
        $hoursDifference = $now->diffInHours($eventDate, false);

        if ($hoursDifference < 24) {
            return response()->json([
                'success' => false,
                'message' => 'Refund rejected: Requests less than 24 hours before the event start time are eligible for 0% refund.'
            ], 422);
        }

        $maxAllowedPercentage = 1.0;
        $policyApplied = 'Full refund (>48h before event)';

        if ($hoursDifference >= 24 && $hoursDifference <= 48) {
            $maxAllowedPercentage = 0.5;
            $policyApplied = '50% refund (24-48h before event)';
        }

        $maxRefundable = (float) $payment->amount * $maxAllowedPercentage;
        $totalRequestedAndPastRefunds = (float) $payment->refunded_amount + $refundAmount;

        if ($totalRequestedAndPastRefunds > $maxRefundable) {
            return response()->json([
                'success' => false,
                'message' => "Refund rejected: Request exceeds the maximum refundable threshold of {$maxRefundable} based on policy: {$policyApplied}."
            ], 422);
        }

        // Update refund tracking status
        $payment->refunded_amount = $totalRequestedAndPastRefunds;
        $payment->status = 'refunded';
        $payment->save();

        return response()->json([
            'success' => true,
            'status' => 'completed',
            'refunded_amount' => $refundAmount,
            'policy_applied' => $policyApplied,
            'message' => 'Refund processed successfully.'
        ]);
    }
}
