<?php

use App\Http\Controllers\Admin\Account\AccountDestroyController;
use App\Http\Controllers\Admin\Account\AccountFormController;
use App\Http\Controllers\Admin\Account\AccountIndexController;
use App\Http\Controllers\Admin\Account\AccountStoreController;
use App\Http\Controllers\Admin\Account\AccountUpdateController;
use App\Http\Controllers\Admin\AfterRegistrationDeliveryController as AdminAfterRegistrationDeliveryController;
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
use App\Http\Controllers\Admin\MemberTagController as AdminMemberTagController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Admin\QueueController as AdminQueueController;
use App\Http\Controllers\Admin\ReservationController as AdminReservationController;
use App\Http\Controllers\Admin\RfmController as AdminRfmController;
use App\Http\Controllers\Admin\Settings\AdvancedController as SettingsAdvancedController;
use App\Http\Controllers\Admin\Settings\AnnouncementsController;
use App\Http\Controllers\Admin\Settings\BasicController as SettingsBasicController;
use App\Http\Controllers\Admin\Settings\CouponsController;
use App\Http\Controllers\Admin\Settings\FeaturesController as SettingsFeaturesController;
use App\Http\Controllers\Admin\Settings\GalleryController;
use App\Http\Controllers\Admin\Settings\HomeController as SettingsHomeController;
use App\Http\Controllers\Admin\Settings\LiffConnectionController as SettingsLiffConnectionController;
use App\Http\Controllers\Admin\Settings\LiffDesignerController as SettingsLiffDesignerController;
use App\Http\Controllers\Admin\Settings\LotteryController as SettingsLotteryController;
use App\Http\Controllers\Admin\Settings\MenuController as SettingsMenuController;
use App\Http\Controllers\Admin\Settings\ProfileController;
use App\Http\Controllers\Admin\Settings\QueueController;
use App\Http\Controllers\Admin\Settings\RanksController as SettingsRanksController;
use App\Http\Controllers\Admin\Settings\ReservationsController;
use App\Http\Controllers\Admin\Settings\SmaregiController;
use App\Http\Controllers\Admin\Settings\WalletController;
use App\Http\Controllers\Admin\ShopAppUiResearchController;
use App\Http\Controllers\Admin\StampController as AdminStampController;
use App\Http\Controllers\Admin\SystemAdminDashboardController;
use App\Http\Controllers\GoogleWalletSaveRedirectController;
use App\Http\Controllers\LiffController;
use App\Http\Controllers\Onboarding\OnboardingPageController;
use App\Http\Controllers\Queue\QueueOpenController;
use App\Http\Controllers\Smaregi\SmaregiIdpController;
use App\Http\Controllers\Smaregi\SmaregiWebhookController;
use App\Http\Controllers\WalletAddPageController;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

// ========== 公開 ==========
Route::get('/', function () {
    if (auth('admin')->check()) {
        return redirect()->route('admin.root');
    }

    return view('landing');
});

Route::get('/api', function () {
    return response()->json(['message' => 'Reggit API']);
});

// ELB / ロードバランサー用ヘルスチェック（EB の「パス」を /health に設定すること）
// 既定: DB 到達時 plain `ok`。?deep=1 で JSON {status,db} を返す。DB 障害時は 503。
Route::get('/health', function (Request $request) {
    $dbOk = false;
    try {
        DB::select('select 1');
        $dbOk = true;
    } catch (Throwable) {
        $dbOk = false;
    }

    if ($request->query('deep') === '1') {
        return response()->json(
            ['status' => $dbOk ? 'ok' : 'error', 'db' => $dbOk],
            $dbOk ? 200 : 503
        );
    }

    if (! $dbOk) {
        return response('error', 503, ['Content-Type' => 'text/plain']);
    }

    return response('ok', 200, ['Content-Type' => 'text/plain']);
});

// スマレジ: デベロッパーズに「/api」無しで登録した場合の受け口（POST は CSRF 除外済み）
Route::match(['get', 'head', 'post'], '/smaregi/webhook', [SmaregiWebhookController::class, '__invoke'])
    ->middleware('verify.smaregi.webhook')
    ->name('smaregi.webhook_no_api_prefix');
Route::match(['get', 'head', 'post'], '/smaregi/idp', [SmaregiIdpController::class, '__invoke'])->name('smaregi.idp_no_api_prefix');

// ========== LIFF ==========
Route::prefix('liff')->middleware('bind.project')->group(function () {
    Route::get('/', [LiffController::class, 'index'])->name('liff.index');
    Route::get('/{project_key}', [LiffController::class, 'index'])->name('liff.project');
    // 旧リンク（/liff/{project_key}/project_key=...&queue_token=...）互換。
    Route::get('/{project_key}/{legacy_state}', function (string $project_key, string $legacy_state) {
        parse_str($legacy_state, $params);
        $queueToken = is_array($params) ? ($params['queue_token'] ?? null) : null;
        $queueId = is_array($params) ? ($params['queue_id'] ?? null) : null;

        Log::info('[LIFF] legacy queue url redirected', [
            'project_key' => $project_key,
            'legacy_state' => $legacy_state,
            'queue_token' => $queueToken,
            'queue_id' => $queueId,
        ]);

        $query = array_filter([
            'queue_token' => is_string($queueToken) ? $queueToken : null,
            'queue_id' => is_string($queueId) ? $queueId : null,
        ], fn ($v) => $v !== null && $v !== '');

        return redirect()->to('/liff/'.$project_key.($query === [] ? '' : '?'.http_build_query($query)));
    })->where('legacy_state', 'project_key=.*')->name('liff.project.legacy');
});

// Wallet 追加用の中間ページ（追加後にアプリへ戻るリンクを表示）
Route::get('/wallet-add', WalletAddPageController::class)
    ->middleware('bind.project')
    ->name('wallet-add');
Route::get('/google-wallet/save/{token}', GoogleWalletSaveRedirectController::class)
    ->middleware('bind.project')
    ->name('google-wallet.save');

// 順番待ち QR 起動（LINE / ネイティブ選択）
Route::get('/queue/open/{project_key}/{token}', QueueOpenController::class)
    ->middleware('bind.project')
    ->name('queue.open');

// ========== 公開オンボーディング（スマレジ初回遷移・認証不要） ==========
Route::get('/onboarding', OnboardingPageController::class)->name('onboarding.create');

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
    $projectKey = $first
        ? $first->project_key
        : (config('app.default_project_key') ?: Project::where('is_active', true)->orderBy('id')->value('project_key'));

    if ($projectKey === null || $projectKey === '') {
        return redirect()->route('admin.login');
    }

    return redirect()->route('admin.dashboard', ['project_key' => $projectKey]);
})->middleware('web')->name('admin.root');

// ========== 店舗管理画面（project_key 必須） ==========
Route::prefix('admin')->middleware(['web', 'auth:admin', 'bind.admin.project', 'admin.audit'])->group(function () {
    Route::post('/logout', [LogoutController::class, '__invoke'])->name('admin.logout')->withoutMiddleware('bind.admin.project');

    Route::prefix('{project_key}')->group(function () {
        Route::get('/', function (string $project_key) {
            return redirect()->route('admin.dashboard', ['project_key' => $project_key]);
        })->name('admin.project.home')->middleware('admin.permission:dashboard.view');

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
        Route::get('/members/export', [AdminMemberController::class, 'export'])->name('admin.members.export')->middleware('admin.permission:members.view');
        Route::get('/members/{id}/history/{section}', [AdminMemberController::class, 'memberHistory'])->name('admin.members.history')->middleware('admin.permission:members.view');
        Route::get('/members/{id}/visits/{visitId}', [AdminMemberController::class, 'visitDetail'])->name('admin.members.visits.detail')->middleware('admin.permission:members.view');
        Route::get('/members/{id}/edit', [AdminMemberController::class, 'editMember'])->name('admin.members.edit')->middleware('admin.permission:members.manage');
        Route::get('/members/{id}', [AdminMemberController::class, 'memberDetail'])->name('admin.members.detail')->middleware('admin.permission:members.view');
        Route::put('/members/{id}', [AdminMemberController::class, 'updateMember'])->name('admin.members.update')->middleware('admin.permission:members.manage');
        Route::delete('/members/{id}', [AdminMemberController::class, 'deleteMember'])->name('admin.members.delete')->middleware('admin.permission:members.manage');
        Route::post('/members/{id}/issue-coupon', [AdminMemberController::class, 'issueCoupon'])->name('admin.members.issue-coupon')->middleware('admin.permission:members.manage');
        Route::delete('/members/{id}/coupons/{memberCouponId}', [AdminMemberController::class, 'deleteCoupon'])->name('admin.members.delete-coupon')->middleware('admin.permission:members.manage');
        Route::post('/members/{id}/adjust-stamps', [AdminMemberController::class, 'adjustStamps'])->name('admin.members.adjust-stamps')->middleware('admin.permission:members.manage');
        Route::post('/members/{id}/visits', [AdminMemberController::class, 'recordVisit'])->name('admin.members.record-visit')->middleware('admin.permission:members.manage');
        Route::post('/members/{id}/tags', [AdminMemberController::class, 'attachTag'])->name('admin.members.tags.attach')->middleware('admin.permission:members.manage');
        Route::delete('/members/{id}/tags/{tagId}', [AdminMemberController::class, 'detachTag'])->name('admin.members.tags.detach')->middleware('admin.permission:members.manage');
        Route::put('/members/{id}/memo', [AdminMemberController::class, 'updateMemo'])->name('admin.members.memo')->middleware('admin.permission:members.manage');
        Route::get('/member-tags', [AdminMemberTagController::class, 'index'])->name('admin.member-tags')->middleware('admin.permission:members.view');
        Route::post('/member-tags', [AdminMemberTagController::class, 'store'])->name('admin.member-tags.store')->middleware('admin.permission:members.manage');
        Route::delete('/member-tags/{id}', [AdminMemberTagController::class, 'destroy'])->name('admin.member-tags.destroy')->middleware('admin.permission:members.manage');
        Route::get('/rfm', [AdminRfmController::class, 'index'])->name('admin.rfm')->middleware('admin.permission:members.view');

        Route::get('/reservations', [AdminReservationController::class, 'reservations'])->name('admin.reservations')->middleware('admin.permission:reservations.view');
        Route::get('/reservations/create', [AdminReservationController::class, 'createReservation'])->name('admin.reservations.create')->middleware('admin.permission:reservations.update');
        Route::post('/reservations', [AdminReservationController::class, 'storeReservation'])->name('admin.reservations.store')->middleware('admin.permission:reservations.update');
        Route::get('/reservations/{id}', [AdminReservationController::class, 'reservationDetail'])->name('admin.reservations.detail')->middleware('admin.permission:reservations.view');
        Route::get('/reservations/{id}/edit', [AdminReservationController::class, 'editReservation'])->name('admin.reservations.edit')->middleware('admin.permission:reservations.update');
        Route::put('/reservations/{id}', [AdminReservationController::class, 'updateReservation'])->name('admin.reservations.update')->middleware('admin.permission:reservations.update');
        Route::delete('/reservations/{id}', [AdminReservationController::class, 'destroyReservation'])->name('admin.reservations.destroy')->middleware('admin.permission:reservations.update');
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
        Route::post('/coupons/quick-create', [AdminCouponController::class, 'quickCreateCoupon'])->name('admin.coupons.quick-create')->middleware('admin.permission:coupons.manage');
        Route::post('/coupons/sync-smaregi', [AdminCouponController::class, 'syncCouponsFromSmaregi'])->name('admin.coupons.sync-smaregi')->middleware('admin.permission:coupons.manage');
        Route::post('/coupons/push-smaregi', [AdminCouponController::class, 'pushCouponsToSmaregi'])->name('admin.coupons.push-smaregi')->middleware('admin.permission:coupons.manage');
        Route::post('/coupons/full-sync-smaregi', [AdminCouponController::class, 'fullSyncCouponsWithSmaregi'])->name('admin.coupons.full-sync-smaregi')->middleware('admin.permission:coupons.manage');
        Route::get('/coupons/ajax/members', [AdminCouponController::class, 'searchMembers'])->name('admin.coupons.ajax.members')->middleware('admin.permission:coupons.manage');
        Route::get('/coupons/delivery-preview', [AdminCouponController::class, 'deliveryPreview'])->name('admin.coupons.delivery.preview')->middleware('admin.permission:coupons.manage');
        Route::get('/coupons/delivery-preview-members', [AdminCouponController::class, 'deliveryPreviewMembers'])->name('admin.coupons.delivery.preview-members')->middleware('admin.permission:coupons.manage');
        Route::get('/coupons/deliveries/{id}', [AdminCouponController::class, 'deliveryShow'])->name('admin.coupons.deliveries.show')->middleware('admin.permission:coupons.manage');
        Route::get('/coupons/{id}/edit', [AdminCouponController::class, 'editCoupon'])->name('admin.coupons.edit')->middleware('admin.permission:coupons.manage');
        Route::get('/coupons/{id}/redemptions', [AdminCouponController::class, 'couponRedemptions'])->name('admin.coupons.redemptions')->middleware('admin.permission:coupons.view');
        Route::put('/coupons/{id}', [AdminCouponController::class, 'updateCoupon'])->name('admin.coupons.update')->middleware('admin.permission:coupons.manage');
        Route::delete('/coupons/{id}', [AdminCouponController::class, 'deleteCoupon'])->name('admin.coupons.delete')->middleware('admin.permission:coupons.manage');

        Route::get('/announcements', [AdminAnnouncementController::class, 'announcements'])->name('admin.announcements')->middleware('admin.permission:announcements.view');
        Route::get('/announcements/create', [AdminAnnouncementController::class, 'createAnnouncement'])->name('admin.announcements.create')->middleware('admin.permission:announcements.manage');
        Route::post('/announcements', [AdminAnnouncementController::class, 'storeAnnouncement'])->name('admin.announcements.store')->middleware('admin.permission:announcements.manage');
        Route::get('/announcements/delivery', [AdminAnnouncementController::class, 'createDelivery'])->name('admin.announcements.delivery.create')->middleware(['admin.permission:announcements.manage', 'admin.plan:announcement_delivery']);
        Route::post('/announcements/delivery', [AdminAnnouncementController::class, 'storeDelivery'])->name('admin.announcements.delivery.store')->middleware(['admin.permission:announcements.manage', 'admin.plan:announcement_delivery']);
        Route::get('/announcements/ajax/members', [AdminAnnouncementController::class, 'searchMembers'])->name('admin.announcements.ajax.members')->middleware('admin.permission:announcements.view');
        Route::get('/announcements/segment-preview', [AdminAnnouncementController::class, 'segmentPreview'])->name('admin.announcements.segment.preview')->middleware('admin.permission:announcements.view');
        Route::get('/announcements/{id}/reads', [AdminAnnouncementController::class, 'announcementReads'])->name('admin.announcements.reads')->middleware('admin.permission:announcements.view');
        Route::get('/announcements/{id}/edit', [AdminAnnouncementController::class, 'editAnnouncement'])->name('admin.announcements.edit')->middleware('admin.permission:announcements.manage');
        Route::put('/announcements/{id}', [AdminAnnouncementController::class, 'updateAnnouncement'])->name('admin.announcements.update')->middleware('admin.permission:announcements.manage');
        Route::delete('/announcements/{id}', [AdminAnnouncementController::class, 'deleteAnnouncement'])->name('admin.announcements.delete')->middleware('admin.permission:announcements.manage');

        Route::get('/after-registration-deliveries', [AdminAfterRegistrationDeliveryController::class, 'index'])->name('admin.after-registration-deliveries.index')->middleware('admin.permission:announcements.view');
        Route::get('/after-registration-deliveries/create', [AdminAfterRegistrationDeliveryController::class, 'create'])->name('admin.after-registration-deliveries.create')->middleware('admin.permission:announcements.manage');
        Route::post('/after-registration-deliveries', [AdminAfterRegistrationDeliveryController::class, 'store'])->name('admin.after-registration-deliveries.store')->middleware('admin.permission:announcements.manage');
        Route::get('/after-registration-deliveries/{id}/edit', [AdminAfterRegistrationDeliveryController::class, 'edit'])->name('admin.after-registration-deliveries.edit')->middleware('admin.permission:announcements.manage');
        Route::put('/after-registration-deliveries/{id}', [AdminAfterRegistrationDeliveryController::class, 'update'])->name('admin.after-registration-deliveries.update')->middleware('admin.permission:announcements.manage');
        Route::delete('/after-registration-deliveries/{id}', [AdminAfterRegistrationDeliveryController::class, 'destroy'])->name('admin.after-registration-deliveries.destroy')->middleware('admin.permission:announcements.manage');

        Route::get('/queue', [AdminQueueController::class, 'queue'])->name('admin.queue')->middleware('admin.permission:queue.view');
        Route::post('/queue/{id}/status', [AdminQueueController::class, 'updateQueueStatus'])->name('admin.queue.status')->middleware('admin.permission:queue.update');

        Route::get('/settings', [SettingsBasicController::class, 'index'])->name('admin.settings.basic')->middleware('admin.permission:settings.view');
        Route::post('/settings/basic', [SettingsBasicController::class, 'update'])->name('admin.settings.basic.update')->middleware('admin.permission:settings.manage');
        Route::post('/settings/kitchen-token/regenerate', [SettingsBasicController::class, 'regenerateKitchenToken'])->name('admin.settings.kitchen-token.regenerate')->middleware('admin.permission:settings.manage');
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
        Route::get('/settings/smaregi', [SmaregiController::class, 'index'])->name('admin.settings.smaregi')->middleware('admin.permission:settings.view');
        Route::post('/settings/smaregi', [SmaregiController::class, 'update'])->name('admin.settings.smaregi.update')->middleware('admin.permission:settings.manage');
        Route::post('/settings/smaregi/sync-products', [SmaregiController::class, 'syncProducts'])->name('admin.settings.smaregi.sync-products')->middleware('admin.permission:settings.manage');
        Route::post('/settings/smaregi/full-sync-products', [SmaregiController::class, 'fullSyncProducts'])->name('admin.settings.smaregi.full-sync-products')->middleware('admin.permission:settings.manage');
        Route::get('/settings/liff-connection', [SettingsLiffConnectionController::class, 'index'])->name('admin.settings.liff-connection')->middleware('admin.permission:settings.view');
        Route::post('/settings/liff-connection', [SettingsLiffConnectionController::class, 'update'])->name('admin.settings.liff-connection.update')->middleware('admin.permission:settings.manage');
        Route::get('/settings/liff-designer', [SettingsLiffDesignerController::class, 'index'])->name('admin.settings.liff-designer')->middleware(['admin.permission:liff_designer.manage', 'admin.plan:liff_designer_advanced']);
        Route::post('/settings/liff-designer', [SettingsLiffDesignerController::class, 'update'])->name('admin.settings.liff-designer.update')->middleware(['admin.permission:liff_designer.manage', 'admin.plan:liff_designer_advanced']);
        Route::get('/settings/reservations', [ReservationsController::class, 'index'])->name('admin.settings.reservations')->middleware('admin.permission:reservations.view');
        Route::post('/settings/reservations', [ReservationsController::class, 'update'])->name('admin.settings.reservations.update')->middleware('admin.permission:settings.manage');
        Route::get('/settings/queue', [QueueController::class, 'index'])->name('admin.settings.queue')->middleware('admin.permission:queue.view');
        Route::post('/settings/queue', [QueueController::class, 'update'])->name('admin.settings.queue.update')->middleware('admin.permission:settings.manage');
        Route::get('/settings/coupons', [CouponsController::class, 'index'])->name('admin.settings.coupons')->middleware('admin.permission:coupons.view');
        Route::post('/settings/coupons', [CouponsController::class, 'update'])->name('admin.settings.coupons.update')->middleware('admin.permission:settings.manage');
        Route::get('/settings/lottery', [SettingsLotteryController::class, 'index'])->name('admin.settings.lottery')->middleware(['admin.permission:coupons.view', 'admin.plan:lottery']);
        Route::post('/settings/lottery', [SettingsLotteryController::class, 'update'])->name('admin.settings.lottery.update')->middleware(['admin.permission:coupons.manage', 'admin.plan:lottery']);
        Route::get('/settings/announcements', [AnnouncementsController::class, 'index'])->name('admin.settings.announcements')->middleware('admin.permission:announcements.view');
        Route::post('/settings/announcements', [AnnouncementsController::class, 'update'])->name('admin.settings.announcements.update')->middleware('admin.permission:settings.manage');
        Route::get('/settings/gallery', [GalleryController::class, 'index'])->name('admin.settings.gallery')->middleware('admin.permission:gallery.view');
        Route::post('/settings/gallery', [GalleryController::class, 'update'])->name('admin.settings.gallery.update')->middleware('admin.permission:settings.manage');
        Route::get('/settings/profile', [ProfileController::class, 'index'])->name('admin.settings.profile')->middleware('admin.permission:settings.view');
        Route::post('/settings/profile', [ProfileController::class, 'update'])->name('admin.settings.profile.update')->middleware('admin.permission:settings.manage');
        Route::get('/settings/wallet', [WalletController::class, 'index'])->name('admin.settings.wallet')->middleware(['admin.permission:settings.view', 'admin.plan:wallet']);
        Route::post('/settings/wallet', [WalletController::class, 'update'])->name('admin.settings.wallet.update')->middleware(['admin.permission:settings.manage', 'admin.plan:wallet']);

        Route::get('/analytics', [AdminAnalyticsController::class, 'analytics'])->name('admin.analytics')->middleware(['admin.permission:analytics.view', 'admin.plan:analytics_advanced']);
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
    Route::get('/projects/input-home', fn () => redirect()->route('onboarding.create'))->name('system-admin.projects.input-home');
    Route::get('/projects/create', fn () => redirect()->route('onboarding.create'))->name('system-admin.projects.create');
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

    Route::get('/shop-app-ui-research', [ShopAppUiResearchController::class, 'index'])->name('system-admin.shop-app-ui-research');
    Route::get('/shop-app-ui-research/asset/{path}', [ShopAppUiResearchController::class, 'asset'])
        ->where('path', '.*')
        ->name('system-admin.shop-app-ui-research.asset');
});
