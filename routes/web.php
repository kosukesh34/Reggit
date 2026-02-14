<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\ApiTokenController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\LiffController;

Route::middleware('bind.project')->get('/', function () {
    return view('index');
});

Route::get('/api', function () {
    return response()->json(['message' => 'Mobile Order API']);
});


Route::prefix('liff')->middleware('bind.project')->group(function () {
    Route::get('/', [LiffController::class, 'index'])->name('liff.index');
    Route::get('/products', [LiffController::class, 'products'])->name('liff.products');
    Route::get('/member', [LiffController::class, 'memberCard'])->name('liff.member');
    Route::get('/orders', [LiffController::class, 'orders'])->name('liff.orders');
});


Route::prefix('store')->name('store.')->group(function () {
    Route::get('/login', [LoginController::class, 'showStoreLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'loginStore'])->name('login.submit');
});

// 店舗管理者（admin）: /admin および /admin/{project_key}/...
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [LoginController::class, 'showAdminLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'loginAdmin'])->name('login.submit');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth:admin');

    // URL に project_key を含める（例: /admin/default, /admin/default/orders）
    Route::middleware(['auth:admin', 'store.admin', 'bind.admin.project', 'admin.audit'])->prefix('{project_key}')->group(function () {
        // ダッシュボード
        Route::get('/', [AdminController::class, 'dashboard'])
            ->name('dashboard')
            ->middleware('admin.permission:dashboard.view');

        // 注文管理
        Route::get('/orders', [AdminController::class, 'orders'])
            ->name('orders')
            ->middleware('admin.permission:orders.view');
        Route::get('/orders/{id}', [AdminController::class, 'orderDetail'])
            ->name('orders.detail')
            ->middleware('admin.permission:orders.view');
        Route::post('/orders/{id}/status', [AdminController::class, 'updateOrderStatus'])
            ->name('orders.status')
            ->middleware('admin.permission:orders.update');
        Route::post('/orders/{id}/complete', [AdminController::class, 'completeOrder'])
            ->name('orders.complete')
            ->middleware('admin.permission:orders.update');

        // 商品管理
        Route::get('/products', [AdminController::class, 'products'])
            ->name('products')
            ->middleware('admin.permission:products.view');
        Route::get('/products/create', [AdminController::class, 'createProduct'])
            ->name('products.create')
            ->middleware('admin.permission:products.manage');
        Route::post('/products', [AdminController::class, 'storeProduct'])
            ->name('products.store')
            ->middleware('admin.permission:products.manage');
        Route::get('/products/{id}/edit', [AdminController::class, 'editProduct'])
            ->name('products.edit')
            ->middleware('admin.permission:products.manage');
        Route::put('/products/{id}', [AdminController::class, 'updateProduct'])
            ->name('products.update')
            ->middleware('admin.permission:products.manage');
        Route::delete('/products/{id}', [AdminController::class, 'deleteProduct'])
            ->name('products.delete')
            ->middleware('admin.permission:products.manage');

        // 会員管理
        Route::get('/members', [AdminController::class, 'members'])
            ->name('members')
            ->middleware('admin.permission:members.view');
        Route::get('/members/{id}', [AdminController::class, 'memberDetail'])
            ->name('members.detail')
            ->middleware('admin.permission:members.view');

        // 予約管理
        Route::get('/reservations', [AdminController::class, 'reservations'])
            ->name('reservations')
            ->middleware('admin.permission:reservations.view');
        Route::get('/reservations/{id}', [AdminController::class, 'reservationDetail'])
            ->name('reservations.detail')
            ->middleware('admin.permission:reservations.view');
        Route::post('/reservations/{id}/status', [AdminController::class, 'updateReservationStatus'])
            ->name('reservations.status')
            ->middleware('admin.permission:reservations.update');
        Route::post('/reservations/{id}/complete', [AdminController::class, 'completeReservation'])
            ->name('reservations.complete')
            ->middleware('admin.permission:reservations.update');

        // スタンプ管理
        Route::get('/stamps', [AdminController::class, 'stamps'])
            ->name('stamps')
            ->middleware('admin.permission:stamps.view');
        Route::get('/stamps/create', [AdminController::class, 'createStamp'])
            ->name('stamps.create')
            ->middleware('admin.permission:stamps.manage');
        Route::post('/stamps', [AdminController::class, 'storeStamp'])
            ->name('stamps.store')
            ->middleware('admin.permission:stamps.manage');
        Route::get('/stamps/{id}/edit', [AdminController::class, 'editStamp'])
            ->name('stamps.edit')
            ->middleware('admin.permission:stamps.manage');
        Route::put('/stamps/{id}', [AdminController::class, 'updateStamp'])
            ->name('stamps.update')
            ->middleware('admin.permission:stamps.manage');
        Route::delete('/stamps/{id}', [AdminController::class, 'deleteStamp'])
            ->name('stamps.delete')
            ->middleware('admin.permission:stamps.manage');

        // クーポン管理
        Route::get('/coupons', [AdminController::class, 'coupons'])
            ->name('coupons')
            ->middleware('admin.permission:coupons.view');
        Route::get('/coupons/create', [AdminController::class, 'createCoupon'])
            ->name('coupons.create')
            ->middleware('admin.permission:coupons.manage');
        Route::post('/coupons', [AdminController::class, 'storeCoupon'])
            ->name('coupons.store')
            ->middleware('admin.permission:coupons.manage');
        Route::get('/coupons/{id}/edit', [AdminController::class, 'editCoupon'])
            ->name('coupons.edit')
            ->middleware('admin.permission:coupons.manage');
        Route::put('/coupons/{id}', [AdminController::class, 'updateCoupon'])
            ->name('coupons.update')
            ->middleware('admin.permission:coupons.manage');
        Route::delete('/coupons/{id}', [AdminController::class, 'deleteCoupon'])
            ->name('coupons.delete')
            ->middleware('admin.permission:coupons.manage');

        // お知らせ管理
        Route::get('/announcements', [AdminController::class, 'announcements'])
            ->name('announcements')
            ->middleware('admin.permission:announcements.view');
        Route::get('/announcements/create', [AdminController::class, 'createAnnouncement'])
            ->name('announcements.create')
            ->middleware('admin.permission:announcements.manage');
        Route::post('/announcements', [AdminController::class, 'storeAnnouncement'])
            ->name('announcements.store')
            ->middleware('admin.permission:announcements.manage');
        Route::get('/announcements/{id}/edit', [AdminController::class, 'editAnnouncement'])
            ->name('announcements.edit')
            ->middleware('admin.permission:announcements.manage');
        Route::put('/announcements/{id}', [AdminController::class, 'updateAnnouncement'])
            ->name('announcements.update')
            ->middleware('admin.permission:announcements.manage');
        Route::delete('/announcements/{id}', [AdminController::class, 'deleteAnnouncement'])
            ->name('announcements.delete')
            ->middleware('admin.permission:announcements.manage');

        // 順番待ち管理
        Route::get('/queue', [AdminController::class, 'queue'])
            ->name('queue')
            ->middleware('admin.permission:queue.view');
        Route::post('/queue/{id}/status', [AdminController::class, 'updateQueueStatus'])
            ->name('queue.status')
            ->middleware('admin.permission:queue.update');

        // 設定
        Route::get('/settings', [AdminController::class, 'settingsBasic'])
            ->name('settings.basic')
            ->middleware('admin.permission:settings.view');
        Route::post('/settings/basic', [AdminController::class, 'updateSettingsBasic'])
            ->name('settings.basic.update')
            ->middleware('admin.permission:settings.manage');
        Route::get('/settings/advanced', [AdminController::class, 'settingsAdvanced'])
            ->name('settings.advanced')
            ->middleware('admin.permission:settings.view');
        Route::post('/settings/advanced', [AdminController::class, 'updateSettingsAdvanced'])
            ->name('settings.advanced.update')
            ->middleware('admin.permission:settings.manage');
        Route::get('/settings/menu', [AdminController::class, 'settingsMenu'])
            ->name('settings.menu')
            ->middleware('admin.permission:settings.view');
        Route::post('/settings/menu', [AdminController::class, 'updateSettingsMenu'])
            ->name('settings.menu.update')
            ->middleware('admin.permission:settings.manage');
        Route::get('/settings/ranks', [AdminController::class, 'settingsRanks'])
            ->name('settings.ranks')
            ->middleware('admin.permission:settings.view');
        Route::post('/settings/ranks', [AdminController::class, 'updateSettingsRanks'])
            ->name('settings.ranks.update')
            ->middleware('admin.permission:settings.manage');
        Route::get('/settings/features', [AdminController::class, 'settingsFeatures'])
            ->name('settings.features')
            ->middleware('admin.permission:settings.view');
        Route::post('/settings/features', [AdminController::class, 'updateSettingsFeatures'])
            ->name('settings.features.update')
            ->middleware('admin.permission:settings.manage');
        Route::get('/settings/liff-designer', [AdminController::class, 'settingsLiffDesigner'])
            ->name('settings.liff-designer');
        Route::post('/settings/liff-designer', [AdminController::class, 'updateSettingsLiffDesigner'])
            ->name('settings.liff-designer.update');

        Route::get('/analytics', [AdminController::class, 'analytics'])
            ->name('analytics')
            ->middleware('admin.permission:analytics.view');

        Route::prefix('gallery')->name('gallery.')->group(function () {
            Route::get('/categories', [AdminController::class, 'galleryCategories'])
                ->name('categories')
                ->middleware('admin.permission:gallery.view');
            Route::post('/categories', [AdminController::class, 'storeGalleryCategory'])
                ->name('categories.store')
                ->middleware('admin.permission:gallery.manage');
            Route::put('/categories/{id}', [AdminController::class, 'updateGalleryCategory'])
                ->name('categories.update')
                ->middleware('admin.permission:gallery.manage');
            Route::delete('/categories/{id}', [AdminController::class, 'deleteGalleryCategory'])
                ->name('categories.delete')
                ->middleware('admin.permission:gallery.manage');

            Route::get('/items', [AdminController::class, 'galleryItems'])
                ->name('items')
                ->middleware('admin.permission:gallery.view');
            Route::post('/items', [AdminController::class, 'storeGalleryItem'])
                ->name('items.store')
                ->middleware('admin.permission:gallery.manage');
            Route::put('/items/{id}', [AdminController::class, 'updateGalleryItem'])
                ->name('items.update')
                ->middleware('admin.permission:gallery.manage');
            Route::delete('/items/{id}', [AdminController::class, 'deleteGalleryItem'])
                ->name('items.delete')
                ->middleware('admin.permission:gallery.manage');
        });

        // プロジェクト内アカウント管理（project_admin / admin が使用）
        Route::prefix('accounts')->name('accounts.')->group(function () {
            Route::get('/', [AccountController::class, 'index'])->name('index');
            Route::get('/create', [AccountController::class, 'create'])->name('create');
            Route::post('/', [AccountController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [AccountController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AccountController::class, 'update'])->name('update');
            Route::delete('/{id}', [AccountController::class, 'destroy'])->name('destroy');
        });
    });
});

// システムアドミン（system-admin）: /system-admin
Route::prefix('system-admin')->name('system-admin.')->group(function () {
    Route::get('/login', [LoginController::class, 'showSystemAdminLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'loginSystemAdmin'])->name('login.submit');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth:admin');

    Route::middleware(['auth:admin', 'system.admin', 'admin.audit'])->group(function () {
        Route::get('/', function () {
            $projectsCount = \App\Models\Project::where('is_active', true)->count();
            $accountsCount = \App\Models\AdminUser::count();
            $apiTokensCount = \App\Models\ApiToken::where('is_active', true)->count();
            return view('admin.system-admin.dashboard', compact('projectsCount', 'accountsCount', 'apiTokensCount'));
        })->name('dashboard');
        Route::prefix('projects')->name('projects.')->group(function () {
            Route::get('/', [ProjectController::class, 'index'])->name('index');
            Route::get('/create', [ProjectController::class, 'create'])->name('create');
            Route::post('/', [ProjectController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [ProjectController::class, 'edit'])->name('edit');
            Route::put('/{id}', [ProjectController::class, 'update'])->name('update');
        });
        Route::prefix('api-tokens')->name('api-tokens.')->group(function () {
            Route::get('/', [ApiTokenController::class, 'index'])->name('index');
            Route::get('/create', [ApiTokenController::class, 'create'])->name('create');
            Route::post('/', [ApiTokenController::class, 'store'])->name('store');
            Route::post('/{id}/revoke', [ApiTokenController::class, 'revoke'])->name('revoke');
            Route::get('/{id}/edit', [ApiTokenController::class, 'edit'])->name('edit');
            Route::put('/{id}', [ApiTokenController::class, 'update'])->name('update');
            Route::post('/{id}/rotate', [ApiTokenController::class, 'rotate'])->name('rotate');
        });
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/audit-logs/{id}', [AuditLogController::class, 'show'])->name('audit-logs.show');
        Route::prefix('accounts')->name('accounts.')->group(function () {
            Route::get('/', [AccountController::class, 'index'])->name('index');
            Route::get('/create', [AccountController::class, 'create'])->name('create');
            Route::post('/', [AccountController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [AccountController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AccountController::class, 'update'])->name('update');
            Route::delete('/{id}', [AccountController::class, 'destroy'])->name('destroy');
        });
    });
});
