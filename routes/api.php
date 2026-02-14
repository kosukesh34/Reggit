<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LineWebhookController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\Api\V1\ProductController as V1ProductController;
use App\Http\Controllers\Api\V1\OrderController as V1OrderController;
use App\Http\Controllers\Api\V1\MemberController as V1MemberController;
use App\Http\Controllers\Api\V1\ReservationController as V1ReservationController;
use App\Http\Controllers\Api\V1\AnnouncementController as V1AnnouncementController;
use App\Http\Controllers\Api\V1\StampController as V1StampController;
use App\Http\Controllers\Api\V1\CouponController as V1CouponController;
use App\Http\Controllers\Api\V1\QueueController as V1QueueController;
use App\Http\Controllers\Api\V1\PaymentController as V1PaymentController;

Route::match(['get', 'post'], '/line/webhook', [LineWebhookController::class, 'handle']);

Route::middleware(['web', 'bind.project'])->group(function () {
    Route::get('/announcements', [\App\Http\Controllers\AnnouncementController::class, 'index']);
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::get('/gallery', [\App\Http\Controllers\GalleryController::class, 'index']);
    Route::post('/contact', [\App\Http\Controllers\ContactController::class, 'store']);

    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);

    Route::get('/members/me', [MemberController::class, 'me']);
    Route::put('/members/me', [MemberController::class, 'update']);
    Route::post('/members/register', [MemberController::class, 'register']);
    Route::get('/members/points', [MemberController::class, 'getPoints']);
    Route::post('/members/points/add', [MemberController::class, 'addPoints']);

    Route::get('/stamps', [\App\Http\Controllers\StampController::class, 'index']);
    Route::get('/members/me/coupons', [\App\Http\Controllers\CouponController::class, 'myCoupons']);
    Route::post('/queue', [\App\Http\Controllers\QueueController::class, 'store']);
    Route::get('/queue/me', [\App\Http\Controllers\QueueController::class, 'me']);

    Route::post('/payment/create-intent', [\App\Http\Controllers\PaymentController::class, 'createPaymentIntent']);
    Route::post('/payment/confirm', [\App\Http\Controllers\PaymentController::class, 'confirmPayment']);

    Route::get('/reservations', [ReservationController::class, 'index']);
    Route::get('/reservations/available-dates', [ReservationController::class, 'getAvailableDates']);
    Route::post('/reservations', [ReservationController::class, 'store']);
    Route::put('/reservations/{id}', [ReservationController::class, 'update']);
    Route::post('/reservations/{id}/cancel', [ReservationController::class, 'cancel']);
});

Route::post('/payment/webhook', [\App\Http\Controllers\PaymentController::class, 'handleWebhook']);

// API v1 (token-based)
Route::prefix('v1')->middleware(['api', 'api.auth'])->group(function () {
    Route::get('/announcements', [V1AnnouncementController::class, 'index']);
    Route::get('/products', [V1ProductController::class, 'index']);
    Route::get('/products/{id}', [V1ProductController::class, 'show']);

    Route::post('/orders', [V1OrderController::class, 'store']);
    Route::get('/orders', [V1OrderController::class, 'index']);
    Route::get('/orders/{id}', [V1OrderController::class, 'show']);

    Route::get('/members/me', [V1MemberController::class, 'me']);
    Route::put('/members/me', [V1MemberController::class, 'update']);
    Route::post('/members/register', [V1MemberController::class, 'register']);
    Route::get('/members/points', [V1MemberController::class, 'getPoints']);
    Route::post('/members/points/add', [V1MemberController::class, 'addPoints']);

    Route::get('/stamps', [V1StampController::class, 'index']);
    Route::get('/members/me/coupons', [V1CouponController::class, 'myCoupons']);
    Route::post('/queue', [V1QueueController::class, 'store']);
    Route::get('/queue/me', [V1QueueController::class, 'me']);

    Route::post('/payment/create-intent', [V1PaymentController::class, 'createPaymentIntent']);
    Route::post('/payment/confirm', [V1PaymentController::class, 'confirmPayment']);

    Route::get('/reservations', [V1ReservationController::class, 'index']);
    Route::get('/reservations/available-dates', [V1ReservationController::class, 'getAvailableDates']);
    Route::post('/reservations', [V1ReservationController::class, 'store']);
    Route::put('/reservations/{id}', [V1ReservationController::class, 'update']);
    Route::post('/reservations/{id}/cancel', [V1ReservationController::class, 'cancel']);
});
