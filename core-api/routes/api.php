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



Route::post('/register', [ApiAuthController::class, 'register']);
Route::post('/login', [ApiAuthController::class, 'login']);

Route::get('/hello', function () {
    return response()->json(['message' => 'Hello, World!']);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::prefix('v1')
    ->middleware('auth:sanctum')
    ->group(function () {

        // Custom route for approving a vendor (Admin only)
        Route::post('vendors/approve', [VendorController::class, 'approve']);

        Route::apiResource(
            'events',
            EventController::class
        );

        Route::apiResource(
            'vendors',
            VendorController::class
        );

        Route::apiResource(
            'ticket-type',
            TicketTypeController::class
        );

        Route::apiResource(
            'orders',
            OrderController::class
        );

        Route::apiResource(
            'order-items',
            OrderItemController::class
        );

        Route::apiResource(
            'payments',
            PaymentController::class
        );

        Route::apiResource(
            'refunds',
            RefundController::class
        );

        Route::apiResource(
            'payout-batches',
            PayoutBatchController::class
        );

        Route::apiResource(
            'payouts',
            PayoutController::class
        );

        Route::apiResource(
            'notifications',
            NotificationController::class
        );

        Route::apiResource(
            'webhooks',
            WebhookController::class
        );

        Route::apiResource(
            'disputes',
            DisputeController::class
        );

        // Admin-only operations
        Route::post('disputes/{dispute}/resolve', [DisputeController::class, 'resolve']);
    });

// Webhook callback endpoint for payment microservice status updates
Route::post('v1/webhooks/payment', [WebhookController::class, 'handlePaymentCallback'])
    ->middleware('shared.secret');















