<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

Route::middleware('shared.secret')->group(function () {
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::post('/payments/refund', [PaymentController::class, 'refund']);
});
