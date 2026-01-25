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
    Route::get('/products', [AdminController::class, 'products'])->name('admin.products');
    Route::get('/products/create', [AdminController::class, 'createProduct'])->name('admin.products.create');
    Route::post('/products', [AdminController::class, 'storeProduct'])->name('admin.products.store');
    Route::get('/products/{id}/edit', [AdminController::class, 'editProduct'])->name('admin.products.edit');
    Route::put('/products/{id}', [AdminController::class, 'updateProduct'])->name('admin.products.update');
    Route::delete('/products/{id}', [AdminController::class, 'deleteProduct'])->name('admin.products.delete');
    
    Route::get('/orders', [AdminController::class, 'orders'])->name('admin.orders');
    Route::get('/orders/{id}', [AdminController::class, 'orderDetail'])->name('admin.orders.detail');
    Route::post('/orders/{id}/status', [AdminController::class, 'updateOrderStatus'])->name('admin.orders.status');
    Route::post('/orders/{id}/complete', [AdminController::class, 'completeOrder'])->name('admin.orders.complete');
    
    Route::get('/members', [AdminController::class, 'members'])->name('admin.members');
    Route::get('/members/{id}', [AdminController::class, 'memberDetail'])->name('admin.members.detail');
});

