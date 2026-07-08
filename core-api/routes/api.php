<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\CheckoutController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// 🎟️ EventHub V1 Core API Routes
Route::prefix('v1')->group(function () {
    Route::post('/checkout/reserve', [CheckoutController::class, 'reserve']);
    Route::post('/checkout/confirm', [CheckoutController::class, 'confirm']);
});