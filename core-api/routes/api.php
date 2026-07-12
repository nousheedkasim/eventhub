<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiAuthController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\VendorController;
use App\Http\Controllers\Api\V1\TicketTypeController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\OrderItemController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\RefundController;
use App\Http\Controllers\Api\V1\PayoutBatchController;
use App\Http\Controllers\Api\V1\PayoutController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\WebhookController;
use App\Http\Controllers\Api\V1\DisputeController;

// Public routes
Route::prefix('v1')->group(function () {
    Route::post('register', [ApiAuthController::class, 'register']);
    Route::post('login', [ApiAuthController::class, 'login']);

    // Public event browsing
    Route::get('events', [EventController::class, 'index']);
    Route::get('events/{event}', [EventController::class, 'show']);
    Route::get('events/{event}/ticket-types', [TicketTypeController::class, 'getByEvent']);
});

// Authenticated routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    // User profile
    Route::get('user', function (Request $request) {
        $user = $request->user();
        if ($user && $user->type === 'vendor') {
            $user->load('vendor');
        }
        return response()->json(['success' => true, 'data' => $user, 'message' => 'User profile retrieved']);
    });

    // Vendor-only routes
    Route::middleware('role:vendor')->group(function () {
        Route::post('events', [EventController::class, 'store']);
        Route::put('events/{event}', [EventController::class, 'update']);
        Route::delete('events/{event}', [EventController::class, 'destroy']);

        Route::apiResource('ticket-type', TicketTypeController::class)->except(['index', 'show']);

        Route::post('webhooks/register', [WebhookController::class, 'registerVendorWebhook']);
        Route::get('webhooks/vendor', [WebhookController::class, 'getVendorWebhooks']);
    });

    // Attendee-only routes
    Route::middleware('role:attendee')->group(function () {
        Route::post('orders', [OrderController::class, 'store']);
        Route::apiResource('orders', OrderController::class)->except(['store']);
    });

    // Vendor-only: payments for their orders
    Route::middleware('role:vendor,attendee,admin')->group(function () {
        Route::get('orders', [OrderController::class, 'index']);
    });

    // Payment processing (attendee creates, vendor views own)
    Route::middleware('role:attendee')->group(function () {
        Route::post('payments', [PaymentController::class, 'store']);
    });
    Route::middleware('role:vendor')->group(function () {
        Route::get('payments', [PaymentController::class, 'index']);
        Route::get('payments/{payment}', [PaymentController::class, 'show']);
    });

    // Vendor-specific data (own data only)
    Route::middleware('role:vendor,admin')->group(function () {
        Route::get('payouts', [PayoutController::class, 'index']);
        Route::get('webhooks', [WebhookController::class, 'index']);
    });

    // Admin-only routes
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('vendors', VendorController::class);
        Route::post('vendors/{vendor}/approve', [VendorController::class, 'approve']);
        Route::post('vendors/{vendor}/reject', [VendorController::class, 'reject']);

        Route::apiResource('payout-batches', PayoutBatchController::class);
        Route::apiResource('refunds', RefundController::class);
        Route::apiResource('notifications', NotificationController::class);
        Route::apiResource('disputes', DisputeController::class);
        Route::post('disputes/{dispute}/resolve', [DisputeController::class, 'resolve']);
    });
});

// Payment webhook callback (shared secret auth)
Route::post('v1/webhooks/payment', [WebhookController::class, 'handlePaymentCallback'])
    ->middleware('shared.secret');
