<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LineWebhookController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Member\MemberMeController;
use App\Http\Controllers\Member\MemberUpdateController;
use App\Http\Controllers\Member\MemberRegisterController;
use App\Http\Controllers\Member\MemberPointsController;
use App\Http\Controllers\Member\MemberAddPointsController;
use App\Http\Controllers\Payment\PaymentCreateIntentController;
use App\Http\Controllers\Payment\PaymentConfirmController;
use App\Http\Controllers\Payment\PaymentWebhookController;
use App\Http\Controllers\Reservation\ReservationIndexController;
use App\Http\Controllers\Reservation\ReservationAvailableDatesController;
use App\Http\Controllers\Reservation\ReservationStoreController;
use App\Http\Controllers\Reservation\ReservationUpdateController;
use App\Http\Controllers\Reservation\ReservationCancelController;

Route::match(['get', 'post'], '/line/webhook', [LineWebhookController::class, 'handle']);

Route::middleware(['web'])->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);

    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);

    Route::get('/members/me', [MemberMeController::class, '__invoke']);
    Route::put('/members/me', [MemberUpdateController::class, '__invoke']);
    Route::post('/members/register', [MemberRegisterController::class, '__invoke']);
    Route::get('/members/points', [MemberPointsController::class, '__invoke']);
    Route::post('/members/points/add', [MemberAddPointsController::class, '__invoke']);

    Route::post('/payment/create-intent', [PaymentCreateIntentController::class, '__invoke']);
    Route::post('/payment/confirm', [PaymentConfirmController::class, '__invoke']);

    Route::get('/reservations', [ReservationIndexController::class, '__invoke']);
    Route::get('/reservations/available-dates', [ReservationAvailableDatesController::class, '__invoke']);
    Route::post('/reservations', [ReservationStoreController::class, '__invoke']);
    Route::put('/reservations/{id}', [ReservationUpdateController::class, '__invoke']);
    Route::post('/reservations/{id}/cancel', [ReservationCancelController::class, '__invoke']);
});

Route::post('/payment/webhook', [PaymentWebhookController::class, '__invoke']);
