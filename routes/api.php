<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LineWebhookController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\MemberController;



Route::match(['get', 'post'], '/line/webhook', [LineWebhookController::class, 'handle']);


Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);


Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders', [OrderController::class, 'index']);
Route::get('/orders/{id}', [OrderController::class, 'show']);


Route::get('/members/me', [MemberController::class, 'me']);
Route::post('/members/register', [MemberController::class, 'register']);
Route::get('/members/points', [MemberController::class, 'getPoints']);
Route::post('/members/points/add', [MemberController::class, 'addPoints']);


Route::post('/payment/create-intent', [\App\Http\Controllers\PaymentController::class, 'createPaymentIntent']);
Route::post('/payment/confirm', [\App\Http\Controllers\PaymentController::class, 'confirmPayment']);


Route::post('/payment/webhook', [\App\Http\Controllers\PaymentController::class, 'handleWebhook']);

