<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\LiffController;

Route::get('/', function () {
    return view('index');
});

Route::get('/api', function () {
    return response()->json(['message' => 'Mobile Order API']);
});


Route::prefix('liff')->group(function () {
    Route::get('/', [LiffController::class, 'index'])->name('liff.index');
    Route::get('/products', [LiffController::class, 'products'])->name('liff.products');
    Route::get('/member', [LiffController::class, 'memberCard'])->name('liff.member');
    Route::get('/orders', [LiffController::class, 'orders'])->name('liff.orders');
});


Route::prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');

    Route::get('/orders', [AdminController::class, 'orders'])->name('admin.orders');
    Route::get('/orders/{id}', [AdminController::class, 'orderDetail'])->name('admin.orders.detail');
    Route::post('/orders/{id}/status', [AdminController::class, 'updateOrderStatus'])->name('admin.orders.status');
    Route::post('/orders/{id}/complete', [AdminController::class, 'completeOrder'])->name('admin.orders.complete');

    Route::get('/products', [AdminController::class, 'products'])->name('admin.products');
    Route::get('/products/create', [AdminController::class, 'createProduct'])->name('admin.products.create');
    Route::post('/products', [AdminController::class, 'storeProduct'])->name('admin.products.store');
    Route::get('/products/{id}/edit', [AdminController::class, 'editProduct'])->name('admin.products.edit');
    Route::put('/products/{id}', [AdminController::class, 'updateProduct'])->name('admin.products.update');
    Route::delete('/products/{id}', [AdminController::class, 'deleteProduct'])->name('admin.products.delete');

    Route::get('/members', [AdminController::class, 'members'])->name('admin.members');
    Route::get('/members/{id}', [AdminController::class, 'memberDetail'])->name('admin.members.detail');

    Route::get('/reservations', [AdminController::class, 'reservations'])->name('admin.reservations');
    Route::get('/reservations/{id}', [AdminController::class, 'reservationDetail'])->name('admin.reservations.detail');
    Route::post('/reservations/{id}/status', [AdminController::class, 'updateReservationStatus'])->name('admin.reservations.status');
    Route::post('/reservations/{id}/complete', [AdminController::class, 'completeReservation'])->name('admin.reservations.complete');

    Route::get('/stamps', [AdminController::class, 'stamps'])->name('admin.stamps');
    Route::get('/coupons', [AdminController::class, 'coupons'])->name('admin.coupons');
    Route::get('/announcements', [AdminController::class, 'announcements'])->name('admin.announcements');
    Route::get('/queue', [AdminController::class, 'queue'])->name('admin.queue');

    Route::get('/settings', [AdminController::class, 'settingsBasic'])->name('admin.settings.basic');
    Route::post('/settings/basic', [AdminController::class, 'updateSettingsBasic'])->name('admin.settings.basic.update');
    Route::get('/settings/advanced', [AdminController::class, 'settingsAdvanced'])->name('admin.settings.advanced');
    Route::post('/settings/advanced', [AdminController::class, 'updateSettingsAdvanced'])->name('admin.settings.advanced.update');
});

