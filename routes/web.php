<?php

use App\Http\Controllers\Admin\Account\AccountDestroyController;
use App\Http\Controllers\Admin\Account\AccountFormController;
use App\Http\Controllers\Admin\Account\AccountIndexController;
use App\Http\Controllers\Admin\Account\AccountStoreController;
use App\Http\Controllers\Admin\Account\AccountUpdateController;
use App\Http\Controllers\Admin\AnalyticsController as AdminAnalyticsController;
use App\Http\Controllers\Admin\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\Admin\ApiTokenController as AdminApiTokenController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\Auth\AdminLoginController;
use App\Http\Controllers\Admin\Auth\LogoutController;
use App\Http\Controllers\Admin\Auth\SystemAdminLoginController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Gallery\CategoryController as GalleryCategoryController;
use App\Http\Controllers\Admin\Gallery\ItemController as GalleryItemController;
use App\Http\Controllers\Admin\MemberController as AdminMemberController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Admin\QueueController as AdminQueueController;
use App\Http\Controllers\Admin\ReservationController as AdminReservationController;
use App\Http\Controllers\Admin\Settings\AdvancedController as SettingsAdvancedController;
use App\Http\Controllers\Admin\Settings\BasicController as SettingsBasicController;
use App\Http\Controllers\Admin\Settings\FeaturesController as SettingsFeaturesController;
use App\Http\Controllers\Admin\Settings\HomeController as SettingsHomeController;
use App\Http\Controllers\Admin\Settings\LiffDesignerController as SettingsLiffDesignerController;
use App\Http\Controllers\Admin\Settings\MenuController as SettingsMenuController;
use App\Http\Controllers\Admin\Settings\RanksController as SettingsRanksController;
use App\Http\Controllers\Admin\StampController as AdminStampController;
use App\Http\Controllers\Admin\SystemAdminDashboardController;
use App\Http\Controllers\LiffController;
use App\Http\Controllers\WalletAddPageController;
use App\Models\Project;
use Illuminate\Support\Facades\Route;

// ========== 公開 ==========
Route::get('/', function () {
    return view('index');
});

Route::get('/api', function () {
    return response()->json(['message' => 'reggit API']);
});

// ========== LIFF ==========
Route::prefix('liff')->middleware('bind.project')->group(function () {
    Route::get('/', [LiffController::class, 'index'])->name('liff.index');
    Route::get('/{project_key}', [LiffController::class, 'index'])->name('liff.project');
});

// Wallet 追加用の中間ページ（追加後にアプリへ戻るリンクを表示）
Route::get('/wallet-add', WalletAddPageController::class)->name('wallet-add');

// ========== 管理画面ログイン（認証不要・スロットリング適用） ==========
Route::get('/admin/login', [AdminLoginController::class, 'show'])->name('admin.login');
Route::get('/admin/login/form', [AdminLoginController::class, 'showLoginForm'])->name('admin.login.form');
Route::post('/admin/login', [AdminLoginController::class, 'login'])->name('admin.login.submit')->middleware('throttle:admin-login');
Route::get('/admin/{project_key}/login', [AdminLoginController::class, 'showProject'])->name('admin.project.login');
Route::post('/admin/{project_key}/login', [AdminLoginController::class, 'login'])->name('admin.project.login.submit')->middleware('throttle:admin-login');

Route::get('/system-admin/login', [SystemAdminLoginController::class, 'show'])->name('system-admin.login');
Route::post('/system-admin/login', [SystemAdminLoginController::class, 'login'])->name('system-admin.login.submit')->middleware('throttle:admin-login');

// /admin のみのアクセス時はアクセス可能な先頭プロジェクトへリダイレクト
Route::get('/admin', function () {
    if (! auth('admin')->check()) {
        return redirect()->route('admin.login');
    }
    $admin = auth('admin')->user();
    $first = Project::whereIn('id', $admin->getAllowedProjectIds())
        ->where('is_active', true)
        ->orderBy('name')
        ->first();
    $projectKey = $first ? $first->project_key : 'default';

    return redirect()->route('admin.dashboard', ['project_key' => $projectKey]);
})->middleware('web')->name('admin.root');

// ========== 店舗管理画面（project_key 必須） ==========
Route::prefix('admin')->middleware(['web', 'auth:admin', 'bind.admin.project', 'admin.audit'])->group(function () {
    Route::post('/logout', [LogoutController::class, '__invoke'])->name('admin.logout')->withoutMiddleware('bind.admin.project');

    Route::prefix('{project_key}')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'dashboard'])
            ->name('admin.dashboard')->middleware('admin.permission:dashboard.view');

        Route::get('/orders', [AdminOrderController::class, 'orders'])->name('admin.orders')->middleware('admin.permission:orders.view');
        Route::get('/orders/{id}', [AdminOrderController::class, 'orderDetail'])->name('admin.orders.detail')->middleware('admin.permission:orders.view');
        Route::post('/orders/{id}/status', [AdminOrderController::class, 'updateOrderStatus'])->name('admin.orders.status')->middleware('admin.permission:orders.update');
        Route::post('/orders/{id}/complete', [AdminOrderController::class, 'completeOrder'])->name('admin.orders.complete')->middleware('admin.permission:orders.update');

        Route::get('/products', [AdminProductController::class, 'products'])->name('admin.products')->middleware('admin.permission:products.view');
        Route::get('/products/create', [AdminProductController::class, 'createProduct'])->name('admin.products.create')->middleware('admin.permission:products.manage');
        Route::post('/products', [AdminProductController::class, 'storeProduct'])->name('admin.products.store')->middleware('admin.permission:products.manage');
        Route::get('/products/{id}/edit', [AdminProductController::class, 'editProduct'])->name('admin.products.edit')->middleware('admin.permission:products.manage');
        Route::put('/products/{id}', [AdminProductController::class, 'updateProduct'])->name('admin.products.update')->middleware('admin.permission:products.manage');
        Route::delete('/products/{id}', [AdminProductController::class, 'deleteProduct'])->name('admin.products.delete')->middleware('admin.permission:products.manage');

        Route::get('/members', [AdminMemberController::class, 'members'])->name('admin.members')->middleware('admin.permission:members.view');
        Route::get('/members/{id}/edit', [AdminMemberController::class, 'editMember'])->name('admin.members.edit')->middleware('admin.permission:members.manage');
        Route::get('/members/{id}', [AdminMemberController::class, 'memberDetail'])->name('admin.members.detail')->middleware('admin.permission:members.view');
        Route::put('/members/{id}', [AdminMemberController::class, 'updateMember'])->name('admin.members.update')->middleware('admin.permission:members.manage');
        Route::post('/members/{id}/issue-coupon', [AdminMemberController::class, 'issueCoupon'])->name('admin.members.issue-coupon')->middleware('admin.permission:members.view');
        Route::post('/members/{id}/add-stamps', [AdminMemberController::class, 'addStamps'])->name('admin.members.add-stamps')->middleware('admin.permission:members.view');

        Route::get('/reservations', [AdminReservationController::class, 'reservations'])->name('admin.reservations')->middleware('admin.permission:reservations.view');
        Route::get('/reservations/{id}', [AdminReservationController::class, 'reservationDetail'])->name('admin.reservations.detail')->middleware('admin.permission:reservations.view');
        Route::post('/reservations/{id}/status', [AdminReservationController::class, 'updateReservationStatus'])->name('admin.reservations.status')->middleware('admin.permission:reservations.update');
        Route::post('/reservations/{id}/complete', [AdminReservationController::class, 'completeReservation'])->name('admin.reservations.complete')->middleware('admin.permission:reservations.update');

        Route::get('/stamps', [AdminStampController::class, 'stamps'])->name('admin.stamps')->middleware('admin.permission:stamps.view');
        Route::get('/stamps/create', [AdminStampController::class, 'createStamp'])->name('admin.stamps.create')->middleware('admin.permission:stamps.manage');
        Route::post('/stamps', [AdminStampController::class, 'storeStamp'])->name('admin.stamps.store')->middleware('admin.permission:stamps.manage');
        Route::get('/stamps/{id}/edit', [AdminStampController::class, 'editStamp'])->name('admin.stamps.edit')->middleware('admin.permission:stamps.manage');
        Route::put('/stamps/{id}', [AdminStampController::class, 'updateStamp'])->name('admin.stamps.update')->middleware('admin.permission:stamps.manage');
        Route::delete('/stamps/{id}', [AdminStampController::class, 'deleteStamp'])->name('admin.stamps.delete')->middleware('admin.permission:stamps.manage');

        Route::get('/coupons', [AdminCouponController::class, 'coupons'])->name('admin.coupons')->middleware('admin.permission:coupons.view');
        Route::get('/coupons/create', [AdminCouponController::class, 'createCoupon'])->name('admin.coupons.create')->middleware('admin.permission:coupons.manage');
        Route::post('/coupons', [AdminCouponController::class, 'storeCoupon'])->name('admin.coupons.store')->middleware('admin.permission:coupons.manage');
        Route::get('/coupons/bulk-issue', [AdminCouponController::class, 'bulkIssueForm'])->name('admin.coupons.bulk-issue')->middleware('admin.permission:coupons.manage');
        Route::post('/coupons/bulk-issue', [AdminCouponController::class, 'bulkIssue'])->name('admin.coupons.bulk-issue.store')->middleware('admin.permission:coupons.manage');
        Route::get('/coupons/{id}/edit', [AdminCouponController::class, 'editCoupon'])->name('admin.coupons.edit')->middleware('admin.permission:coupons.manage');
        Route::put('/coupons/{id}', [AdminCouponController::class, 'updateCoupon'])->name('admin.coupons.update')->middleware('admin.permission:coupons.manage');
        Route::delete('/coupons/{id}', [AdminCouponController::class, 'deleteCoupon'])->name('admin.coupons.delete')->middleware('admin.permission:coupons.manage');

        Route::get('/announcements', [AdminAnnouncementController::class, 'announcements'])->name('admin.announcements')->middleware('admin.permission:announcements.view');
        Route::get('/announcements/create', [AdminAnnouncementController::class, 'createAnnouncement'])->name('admin.announcements.create')->middleware('admin.permission:announcements.manage');
        Route::post('/announcements', [AdminAnnouncementController::class, 'storeAnnouncement'])->name('admin.announcements.store')->middleware('admin.permission:announcements.manage');
        Route::get('/announcements/delivery', [AdminAnnouncementController::class, 'createDelivery'])->name('admin.announcements.delivery.create')->middleware('admin.permission:announcements.manage');
        Route::post('/announcements/delivery', [AdminAnnouncementController::class, 'storeDelivery'])->name('admin.announcements.delivery.store')->middleware('admin.permission:announcements.manage');
        Route::get('/announcements/ajax/members', [AdminAnnouncementController::class, 'searchMembers'])->name('admin.announcements.ajax.members')->middleware('admin.permission:announcements.view');
        Route::get('/announcements/{id}/edit', [AdminAnnouncementController::class, 'editAnnouncement'])->name('admin.announcements.edit')->middleware('admin.permission:announcements.manage');
        Route::put('/announcements/{id}', [AdminAnnouncementController::class, 'updateAnnouncement'])->name('admin.announcements.update')->middleware('admin.permission:announcements.manage');
        Route::delete('/announcements/{id}', [AdminAnnouncementController::class, 'deleteAnnouncement'])->name('admin.announcements.delete')->middleware('admin.permission:announcements.manage');

        Route::get('/queue', [AdminQueueController::class, 'queue'])->name('admin.queue')->middleware('admin.permission:queue.view');
        Route::post('/queue/{id}/status', [AdminQueueController::class, 'updateQueueStatus'])->name('admin.queue.status')->middleware('admin.permission:queue.update');

        Route::get('/settings', [SettingsBasicController::class, 'index'])->name('admin.settings.basic')->middleware('admin.permission:settings.view');
        Route::post('/settings/basic', [SettingsBasicController::class, 'update'])->name('admin.settings.basic.update')->middleware('admin.permission:settings.manage');
        Route::post('/settings/feature-flags', [SettingsBasicController::class, 'updateFeatureFlags'])->name('admin.settings.feature-flags.update')->middleware('admin.permission:settings.manage');
        Route::get('/settings/advanced', [SettingsAdvancedController::class, 'index'])->name('admin.settings.advanced')->middleware('admin.permission:settings.view');
        Route::post('/settings/advanced', [SettingsAdvancedController::class, 'update'])->name('admin.settings.advanced.update')->middleware('admin.permission:settings.manage');
        Route::get('/settings/menu', [SettingsMenuController::class, 'index'])->name('admin.settings.menu')->middleware('admin.permission:settings.view');
        Route::post('/settings/menu', [SettingsMenuController::class, 'update'])->name('admin.settings.menu.update')->middleware('admin.permission:settings.manage');
        Route::get('/settings/home', [SettingsHomeController::class, 'index'])->name('admin.settings.home')->middleware('admin.permission:settings.view');
        Route::post('/settings/home', [SettingsHomeController::class, 'update'])->name('admin.settings.home.update')->middleware('admin.permission:settings.manage');
        Route::get('/settings/features', [SettingsFeaturesController::class, 'index'])->name('admin.settings.features')->middleware('admin.permission:settings.view');
        Route::post('/settings/features', [SettingsFeaturesController::class, 'update'])->name('admin.settings.features.update')->middleware('admin.permission:settings.manage');
        Route::get('/settings/ranks', [SettingsRanksController::class, 'index'])->name('admin.settings.ranks')->middleware('admin.permission:settings.view');
        Route::post('/settings/ranks', [SettingsRanksController::class, 'update'])->name('admin.settings.ranks.update')->middleware('admin.permission:settings.manage');
        Route::get('/settings/smaregi', [\App\Http\Controllers\Admin\Settings\SmaregiController::class, 'index'])->name('admin.settings.smaregi')->middleware('admin.permission:settings.view');
        Route::get('/settings/smaregi/pos-data', [\App\Http\Controllers\Admin\Settings\SmaregiController::class, 'posData'])->name('admin.settings.smaregi.pos-data')->middleware('admin.permission:settings.view');
        Route::post('/settings/smaregi', [\App\Http\Controllers\Admin\Settings\SmaregiController::class, 'update'])->name('admin.settings.smaregi.update')->middleware('admin.permission:settings.manage');
        Route::post('/settings/smaregi/sync-products', [\App\Http\Controllers\Admin\Settings\SmaregiController::class, 'syncProducts'])->name('admin.settings.smaregi.sync-products')->middleware('admin.permission:settings.manage');
        Route::post('/settings/smaregi/full-sync-products', [\App\Http\Controllers\Admin\Settings\SmaregiController::class, 'fullSyncProducts'])->name('admin.settings.smaregi.full-sync-products')->middleware('admin.permission:settings.manage');
        Route::get('/settings/liff-designer', [SettingsLiffDesignerController::class, 'index'])->name('admin.settings.liff-designer')->middleware('admin.permission:liff_designer.manage');
        Route::post('/settings/liff-designer', [SettingsLiffDesignerController::class, 'update'])->name('admin.settings.liff-designer.update')->middleware('admin.permission:liff_designer.manage');
        Route::get('/settings/reservations', [\App\Http\Controllers\Admin\Settings\ReservationsController::class, 'index'])->name('admin.settings.reservations')->middleware('admin.permission:reservations.view');
        Route::post('/settings/reservations', [\App\Http\Controllers\Admin\Settings\ReservationsController::class, 'update'])->name('admin.settings.reservations.update')->middleware('admin.permission:settings.manage');
        Route::get('/settings/queue', [\App\Http\Controllers\Admin\Settings\QueueController::class, 'index'])->name('admin.settings.queue')->middleware('admin.permission:queue.view');
        Route::post('/settings/queue', [\App\Http\Controllers\Admin\Settings\QueueController::class, 'update'])->name('admin.settings.queue.update')->middleware('admin.permission:settings.manage');
        Route::get('/settings/coupons', [\App\Http\Controllers\Admin\Settings\CouponsController::class, 'index'])->name('admin.settings.coupons')->middleware('admin.permission:coupons.view');
        Route::post('/settings/coupons', [\App\Http\Controllers\Admin\Settings\CouponsController::class, 'update'])->name('admin.settings.coupons.update')->middleware('admin.permission:settings.manage');
        Route::get('/settings/announcements', [\App\Http\Controllers\Admin\Settings\AnnouncementsController::class, 'index'])->name('admin.settings.announcements')->middleware('admin.permission:announcements.view');
        Route::post('/settings/announcements', [\App\Http\Controllers\Admin\Settings\AnnouncementsController::class, 'update'])->name('admin.settings.announcements.update')->middleware('admin.permission:settings.manage');
        Route::get('/settings/gallery', [\App\Http\Controllers\Admin\Settings\GalleryController::class, 'index'])->name('admin.settings.gallery')->middleware('admin.permission:gallery.view');
        Route::post('/settings/gallery', [\App\Http\Controllers\Admin\Settings\GalleryController::class, 'update'])->name('admin.settings.gallery.update')->middleware('admin.permission:settings.manage');
        Route::get('/settings/profile', [\App\Http\Controllers\Admin\Settings\ProfileController::class, 'index'])->name('admin.settings.profile')->middleware('admin.permission:settings.view');
        Route::post('/settings/profile', [\App\Http\Controllers\Admin\Settings\ProfileController::class, 'update'])->name('admin.settings.profile.update')->middleware('admin.permission:settings.manage');
        Route::get('/settings/wallet', [\App\Http\Controllers\Admin\Settings\WalletController::class, 'index'])->name('admin.settings.wallet')->middleware('admin.permission:settings.view');
        Route::post('/settings/wallet', [\App\Http\Controllers\Admin\Settings\WalletController::class, 'update'])->name('admin.settings.wallet.update')->middleware('admin.permission:settings.manage');

        Route::get('/analytics', [AdminAnalyticsController::class, 'analytics'])->name('admin.analytics')->middleware('admin.permission:analytics.view');
        Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->name('admin.audit-logs.index')->middleware('admin.permission:audit_logs.view');
        Route::get('/audit-logs/{id}', [AdminAuditLogController::class, 'show'])->name('admin.audit-logs.show')->middleware('admin.permission:audit_logs.view');

        Route::get('/gallery/categories', [GalleryCategoryController::class, 'index'])->name('admin.gallery.categories')->middleware('admin.permission:gallery.view');
        Route::post('/gallery/categories', [GalleryCategoryController::class, 'store'])->name('admin.gallery.categories.store')->middleware('admin.permission:gallery.manage');
        Route::put('/gallery/categories/{id}', [GalleryCategoryController::class, 'update'])->name('admin.gallery.categories.update')->middleware('admin.permission:gallery.manage');
        Route::delete('/gallery/categories/{id}', [GalleryCategoryController::class, 'delete'])->name('admin.gallery.categories.delete')->middleware('admin.permission:gallery.manage');
        Route::get('/gallery/items', [GalleryItemController::class, 'index'])->name('admin.gallery.items')->middleware('admin.permission:gallery.view');
        Route::post('/gallery/items', [GalleryItemController::class, 'store'])->name('admin.gallery.items.store')->middleware('admin.permission:gallery.manage');
        Route::put('/gallery/items/{id}', [GalleryItemController::class, 'update'])->name('admin.gallery.items.update')->middleware('admin.permission:gallery.manage');
        Route::delete('/gallery/items/{id}', [GalleryItemController::class, 'delete'])->name('admin.gallery.items.delete')->middleware('admin.permission:gallery.manage');
    });
});

// ========== システム管理画面（システム管理者のみ） ==========
Route::prefix('system-admin')->middleware(['web', 'auth:admin', 'system.admin', 'admin.audit'])->group(function () {
    Route::post('/logout', [LogoutController::class, '__invoke'])->name('system-admin.logout');

    Route::get('/dashboard', [SystemAdminDashboardController::class, 'dashboard'])->name('system-admin.dashboard');
    Route::get('/projects', [AdminProjectController::class, 'index'])->name('system-admin.projects.index');
    Route::get('/projects/input-home', [AdminProjectController::class, 'create'])->name('system-admin.projects.input-home');
    Route::get('/projects/create', [AdminProjectController::class, 'create'])->name('system-admin.projects.create');
    Route::post('/projects', [AdminProjectController::class, 'store'])->name('system-admin.projects.store');
    Route::get('/projects/{id}/edit', [AdminProjectController::class, 'edit'])->name('system-admin.projects.edit');
    Route::put('/projects/{id}', [AdminProjectController::class, 'update'])->name('system-admin.projects.update');

    Route::get('/accounts', [AccountIndexController::class, '__invoke'])->name('system-admin.accounts.index');
    Route::get('/accounts/create', [AccountFormController::class, 'create'])->name('system-admin.accounts.create');
    Route::post('/accounts', [AccountStoreController::class, '__invoke'])->name('system-admin.accounts.store');
    Route::get('/accounts/{id}/edit', [AccountFormController::class, 'edit'])->name('system-admin.accounts.edit');
    Route::put('/accounts/{id}', [AccountUpdateController::class, '__invoke'])->name('system-admin.accounts.update');
    Route::delete('/accounts/{id}', [AccountDestroyController::class, '__invoke'])->name('system-admin.accounts.destroy');

    Route::get('/api-tokens', [AdminApiTokenController::class, 'index'])->name('system-admin.api-tokens.index');
    Route::get('/api-tokens/create', [AdminApiTokenController::class, 'create'])->name('system-admin.api-tokens.create');
    Route::get('/api-tokens/{id}/edit', [AdminApiTokenController::class, 'edit'])->name('system-admin.api-tokens.edit');
    Route::post('/api-tokens', [AdminApiTokenController::class, 'store'])->name('system-admin.api-tokens.store');
    Route::put('/api-tokens/{id}', [AdminApiTokenController::class, 'update'])->name('system-admin.api-tokens.update');
    Route::post('/api-tokens/{id}/revoke', [AdminApiTokenController::class, 'revoke'])->name('system-admin.api-tokens.revoke');
    Route::post('/api-tokens/{id}/rotate', [AdminApiTokenController::class, 'rotate'])->name('system-admin.api-tokens.rotate');

    Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->name('system-admin.audit-logs.index');
    Route::get('/audit-logs/{id}', [AdminAuditLogController::class, 'show'])->name('system-admin.audit-logs.show');
});
