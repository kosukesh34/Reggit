<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LineWebhookController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ReservationController;

Route::match(['get', 'post'], '/line/webhook', [LineWebhookController::class, 'handle']);

Route::middleware(['web'])->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    
    Route::get('/members/me', [MemberController::class, 'me']);
    Route::put('/members/me', [MemberController::class, 'update']);
    Route::post('/members/register', [MemberController::class, 'register']);
    Route::get('/members/points', [MemberController::class, 'getPoints']);
    Route::post('/members/points/add', [MemberController::class, 'addPoints']);
    
    Route::post('/payment/create-intent', [\App\Http\Controllers\PaymentController::class, 'createPaymentIntent']);
    Route::post('/payment/confirm', [\App\Http\Controllers\PaymentController::class, 'confirmPayment']);
    
    Route::get('/reservations', [ReservationController::class, 'index']);
    Route::get('/reservations/available-dates', [ReservationController::class, 'getAvailableDates']);
    Route::post('/reservations', [ReservationController::class, 'store']);
    Route::put('/reservations/{id}', [ReservationController::class, 'update']);
    Route::post('/reservations/{id}/cancel', [ReservationController::class, 'cancel']);
});

Route::post('/payment/webhook', [\App\Http\Controllers\PaymentController::class, 'handleWebhook']);

