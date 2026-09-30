<?php

use App\Http\Controllers\Admin\Account\AccountDestroyController;
use App\Http\Controllers\Admin\Account\AccountFormController;
use App\Http\Controllers\Admin\Account\AccountIndexController;
use App\Http\Controllers\Admin\Account\AccountStoreController;
use App\Http\Controllers\Admin\Account\AccountUpdateController;
use App\Http\Controllers\Admin\AdminProjectHomeController;
use App\Http\Controllers\Admin\AdminRootController;
use App\Http\Controllers\Admin\AfterRegistrationDeliveryController as AdminAfterRegistrationDeliveryController;
use App\Http\Controllers\Admin\AfterRegistrationDeliveryMutationController;
use App\Http\Controllers\Admin\AnalysisDeliveryController as AdminAnalysisDeliveryController;
use App\Http\Controllers\Admin\AnalyticsController as AdminAnalyticsController;
use App\Http\Controllers\Admin\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\Admin\AnnouncementDeliveryController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\Auth\AdminLoginController;
use App\Http\Controllers\Admin\Auth\AdminPasswordController;
use App\Http\Controllers\Admin\Auth\LogoutController;
use App\Http\Controllers\Admin\Auth\SystemAdminLoginController;
use App\Http\Controllers\Admin\CouponBulkIssueController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\CouponDeliveryController;
use App\Http\Controllers\Admin\CouponMutationController;
use App\Http\Controllers\Admin\CouponSmaregiSyncController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Gallery\CategoryController as GalleryCategoryController;
use App\Http\Controllers\Admin\Gallery\ItemController as GalleryItemController;
use App\Http\Controllers\Admin\Gallery\ItemMutationController as GalleryItemMutationController;
use App\Http\Controllers\Admin\HelpController as AdminHelpController;
use App\Http\Controllers\Admin\MemberActivityController as AdminMemberActivityController;
use App\Http\Controllers\Admin\MemberBenefitController;
use App\Http\Controllers\Admin\MemberController as AdminMemberController;
use App\Http\Controllers\Admin\MemberHistoryController;
use App\Http\Controllers\Admin\MemberIndexController;
use App\Http\Controllers\Admin\MemberMutationController;
use App\Http\Controllers\Admin\MemberTagController as AdminMemberTagController;
use App\Http\Controllers\Admin\MemberTransferController as AdminMemberTransferController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProductMutationController;
use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Admin\ProjectMutationController;
use App\Http\Controllers\Admin\QueueController as AdminQueueController;
use App\Http\Controllers\Admin\ReservationController as AdminReservationController;
use App\Http\Controllers\Admin\ReservationStatusController;
use App\Http\Controllers\Admin\RfmController as AdminRfmController;
use App\Http\Controllers\Admin\Settings\AdvancedController as SettingsAdvancedController;
use App\Http\Controllers\Admin\Settings\AnnouncementsController;
use App\Http\Controllers\Admin\Settings\BasicController as SettingsBasicController;
use App\Http\Controllers\Admin\Settings\CouponsController;
use App\Http\Controllers\Admin\Settings\ExistingMemberLinkController as SettingsExistingMemberLinkController;
use App\Http\Controllers\Admin\Settings\FeaturesController as SettingsFeaturesController;
use App\Http\Controllers\Admin\Settings\HomeController as SettingsHomeController;
use App\Http\Controllers\Admin\Settings\IndexController as SettingsIndexController;
use App\Http\Controllers\Admin\Settings\LiffConnectionController as SettingsLiffConnectionController;
use App\Http\Controllers\Admin\Settings\LiffDesignerController as SettingsLiffDesignerController;
use App\Http\Controllers\Admin\Settings\LotteryController as SettingsLotteryController;
use App\Http\Controllers\Admin\Settings\MenuController as SettingsMenuController;
use App\Http\Controllers\Admin\Settings\ProfileController;
use App\Http\Controllers\Admin\Settings\QueueController;
use App\Http\Controllers\Admin\Settings\RanksController as SettingsRanksController;
use App\Http\Controllers\Admin\Settings\ReservationsController;
use App\Http\Controllers\Admin\Settings\StaffController as SettingsStaffController;
use App\Http\Controllers\Admin\Settings\StaffMutationController;
use App\Http\Controllers\Admin\Settings\StoreAppCredentialController;
use App\Http\Controllers\Admin\Settings\WalletController;
use App\Http\Controllers\Admin\StampController as AdminStampController;
use App\Http\Controllers\Admin\StampGrantQrController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\Admin\SystemAdminDashboardController;
use App\Http\Controllers\Admin\SystemHelpArticleController;
use App\Http\Controllers\Admin\SystemNoticeController;
use App\Http\Controllers\ApiInfoController;
use App\Http\Controllers\ExistingMemberLinkConfirmController;
use App\Http\Controllers\GoogleWalletSaveRedirectController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\Landing\LandingRegistrationEmailController;
use App\Http\Controllers\Landing\LandingRegistrationFormController;
use App\Http\Controllers\Landing\LandingRegistrationVerifyController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LegalDocumentController;
use App\Http\Controllers\LiffController;
use App\Http\Controllers\LiffLegacyRedirectController;
use App\Http\Controllers\Onboarding\OnboardingPageController;
use App\Http\Controllers\Payment\PaymentReturnController;
use App\Http\Controllers\Queue\QueueOpenController;
use App\Http\Controllers\Smaregi\SmaregiIdpController;
use App\Http\Controllers\Smaregi\SmaregiWebhookController;
use App\Http\Controllers\StampGrantOpenController;
use App\Http\Controllers\WalletAddPageController;
use Illuminate\Support\Facades\Route;

// ========== 公開 ==========
Route::get('/', LandingController::class);
Route::view('/demo/liff', 'demo.liff')->name('demo.liff');

Route::view('/lp/register/email', 'landing.email')->name('landing.register.email.show');
Route::post('/lp/register/email', LandingRegistrationEmailController::class)
    ->middleware('throttle:5,1')
    ->name('landing.register.email');
Route::get('/lp/register/{registration}/verify', LandingRegistrationVerifyController::class)
    ->middleware('signed')
    ->name('landing.register.verify');
Route::get('/lp/register/{registration}/form', [LandingRegistrationFormController::class, 'show'])
    ->middleware('signed')
    ->name('landing.register.form');
Route::post('/lp/register/{registration}/form', [LandingRegistrationFormController::class, 'store'])
    ->middleware('signed')
    ->name('landing.register.form.submit');

Route::get('/existing-member-link/{token}', ExistingMemberLinkConfirmController::class)
    ->middleware('throttle:20,1')
    ->name('existing-member-link.confirm');
Route::get('/legal/{project_key}/terms', [LegalDocumentController::class, 'terms'])->name('legal.terms');
Route::get('/legal/{project_key}/privacy', [LegalDocumentController::class, 'privacy'])->name('legal.privacy');

Route::get('/api', ApiInfoController::class);

// ELB / ロードバランサー用ヘルスチェック（EB の「パス」を /health に設定すること）
// 既定: DB 到達時 plain `ok`。?deep=1 で JSON {status,db} を返す。DB 障害時は 503。
Route::get('/health', HealthController::class);

// スマレジ: デベロッパーズに「/api」無しで登録した場合の受け口（POST は CSRF 除外済み）
Route::match(['get', 'head', 'post'], '/smaregi/webhook', [SmaregiWebhookController::class, '__invoke'])
    ->middleware('verify.smaregi.webhook')
    ->name('smaregi.webhook_no_api_prefix');
Route::match(['get', 'head', 'post'], '/smaregi/idp', [SmaregiIdpController::class, '__invoke'])
    ->middleware('verify.smaregi.idp')
    ->name('smaregi.idp_no_api_prefix');

// ========== LIFF ==========
Route::prefix('liff')->middleware('bind.project')->group(function () {
    Route::get('/', [LiffController::class, 'index'])->name('liff.index');
    Route::get('/{project_key}', [LiffController::class, 'index'])->name('liff.project');
    // 旧リンク（/liff/{project_key}/project_key=...&queue_token=...）互換。
    Route::get('/{project_key}/{legacy_state}', LiffLegacyRedirectController::class)
        ->where('legacy_state', 'project_key=.*')->name('liff.project.legacy');
});

// Wallet 追加用の中間ページ（追加後にアプリへ戻るリンクを表示）
Route::get('/wallet-add', WalletAddPageController::class)
    ->middleware('bind.project')
    ->name('wallet-add');
Route::get('/google-wallet/save/{token}', GoogleWalletSaveRedirectController::class)
    ->middleware('bind.project')
    ->name('google-wallet.save');

// スタンプ付与 QR（短い https URL。LINEのコードリーダーで開く）
Route::get('/g/{token}', StampGrantOpenController::class)
    ->where('token', '[0-9a-f]{12}')
    ->middleware('throttle:60,1')
    ->name('stamp.grant.open');

// 順番待ち QR 起動（LINE / ネイティブ選択）
Route::get('/queue/open/{project_key}/{token}', QueueOpenController::class)
    ->middleware('bind.project')
    ->name('queue.open');

// ========== 公開オンボーディング（スマレジ初回遷移・認証不要） ==========
Route::get('/onboarding', OnboardingPageController::class)->name('onboarding.create');

// ========== 管理画面ログイン（認証不要・スロットリング適用） ==========
Route::get('/admin/forgot-password', [AdminPasswordController::class, 'forgot'])->name('admin.password.request');
Route::post('/admin/forgot-password', [AdminPasswordController::class, 'send'])->middleware('throttle:5,1')->name('admin.password.email');
Route::get('/admin/reset-password/{token}', [AdminPasswordController::class, 'reset'])->name('admin.password.reset');
Route::post('/admin/reset-password', [AdminPasswordController::class, 'update'])->middleware('throttle:5,1')->name('admin.password.update');
Route::get('/admin/change-password', [AdminPasswordController::class, 'edit'])->middleware('auth:admin')->name('admin.password.edit');
Route::put('/admin/change-password', [AdminPasswordController::class, 'change'])->middleware(['auth:admin', 'throttle:5,1'])->name('admin.password.change');

Route::get('/admin/login', [AdminLoginController::class, 'show'])->name('admin.login');
Route::get('/admin/login/form', [AdminLoginController::class, 'showLoginForm'])->name('admin.login.form');
Route::post('/admin/login', [AdminLoginController::class, 'login'])->name('admin.login.submit')->middleware('throttle:admin-login');
Route::get('/admin/{project_key}/login', [AdminLoginController::class, 'showProject'])->name('admin.project.login');
Route::post('/admin/{project_key}/login', [AdminLoginController::class, 'login'])->name('admin.project.login.submit')->middleware('throttle:admin-login');

Route::get('/system-admin/login', [SystemAdminLoginController::class, 'show'])->name('system-admin.login');
Route::post('/system-admin/login', [SystemAdminLoginController::class, 'login'])->name('system-admin.login.submit')->middleware('throttle:admin-login');

// /admin のみのアクセス時はアクセス可能な先頭プロジェクトへリダイレクト
Route::get('/admin', AdminRootController::class)->middleware('web')->name('admin.root');

// ========== 店舗管理画面（project_key 必須） ==========
Route::prefix('admin')->middleware(['web', 'auth:admin', 'bind.admin.project', 'admin.audit'])->group(function () {
    Route::post('/logout', [LogoutController::class, '__invoke'])->name('admin.logout')->withoutMiddleware('bind.admin.project');

    Route::prefix('{project_key}')->group(function () {
        Route::get('/stores', [StoreController::class, 'index'])->name('admin.stores.index');
        Route::post('/stores', [StoreController::class, 'store'])->name('admin.stores.store')->middleware('admin.permission:settings.manage');
        Route::put('/stores/{store}/admins', [StoreController::class, 'updateAdmins'])->name('admin.stores.admins')->middleware('admin.permission:settings.manage');
        Route::put('/stores/active', [StoreController::class, 'updateActiveStores'])->name('admin.stores.active')->middleware('admin.permission:settings.manage');
        Route::post('/stores/select', [StoreController::class, 'select'])->name('admin.stores.select');
        Route::get('/', AdminProjectHomeController::class)
            ->name('admin.project.home')->middleware('admin.permission:dashboard.view');

        Route::get('/dashboard', [DashboardController::class, 'dashboard'])
            ->name('admin.dashboard')->middleware('admin.permission:dashboard.view');
        Route::get('/dashboard/notices/{id}', [DashboardController::class, 'notice'])
            ->name('admin.dashboard.notice')->middleware('admin.permission:dashboard.view');

        Route::get('/orders', [AdminOrderController::class, 'orders'])->name('admin.orders')->middleware('admin.permission:orders.view');
        Route::get('/orders/{id}', [AdminOrderController::class, 'orderDetail'])->name('admin.orders.detail')->middleware('admin.permission:orders.view');
        Route::post('/orders/{id}/status', [AdminOrderController::class, 'updateOrderStatus'])->name('admin.orders.status')->middleware('admin.permission:orders.update');
        Route::post('/orders/{id}/complete', [AdminOrderController::class, 'completeOrder'])->name('admin.orders.complete')->middleware('admin.permission:orders.update');

        Route::get('/products', [AdminProductController::class, 'products'])->name('admin.products')->middleware('admin.permission:products.view');
        Route::post('/products/import-smaregi', [AdminProductController::class, 'importSmaregi'])->name('admin.products.import-smaregi')->middleware('admin.permission:products.manage');
        Route::get('/products/create', [AdminProductController::class, 'createProduct'])->name('admin.products.create')->middleware('admin.permission:products.manage');
        Route::post('/products', [ProductMutationController::class, 'store'])->name('admin.products.store')->middleware('admin.permission:products.manage');
        Route::get('/products/{id}/edit', [AdminProductController::class, 'editProduct'])->name('admin.products.edit')->middleware('admin.permission:products.manage');
        Route::put('/products/{id}', [ProductMutationController::class, 'update'])->name('admin.products.update')->middleware('admin.permission:products.manage');
        Route::delete('/products/{id}', [ProductMutationController::class, 'destroy'])->name('admin.products.delete')->middleware('admin.permission:products.manage');

        Route::get('/members', [MemberIndexController::class, 'index'])->name('admin.members')->middleware('admin.permission:members.view');
        Route::post('/members/{id}/transfer-code', [AdminMemberTransferController::class, 'store'])->name('admin.members.transfer-code')->middleware('admin.permission:members.manage');
        Route::get('/members/export', [MemberIndexController::class, 'export'])->name('admin.members.export')->middleware('admin.permission:members.view');
        Route::get('/members/{id}/history/{section}', [MemberHistoryController::class, 'index'])->name('admin.members.history')->middleware('admin.permission:members.view');
        Route::get('/members/{id}/visits/{visitId}', [MemberHistoryController::class, 'visit'])->name('admin.members.visits.detail')->middleware('admin.permission:members.view');
        Route::get('/members/{id}/edit', [AdminMemberController::class, 'editMember'])->name('admin.members.edit')->middleware('admin.permission:members.manage');
        Route::get('/members/{id}', [AdminMemberController::class, 'memberDetail'])->name('admin.members.detail')->middleware('admin.permission:members.view');
        Route::put('/members/{id}', [MemberMutationController::class, 'update'])->name('admin.members.update')->middleware('admin.permission:members.manage');
        Route::delete('/members/{id}', [MemberMutationController::class, 'destroy'])->name('admin.members.delete')->middleware('admin.permission:members.manage');
        Route::post('/members/{id}/issue-coupon', [MemberBenefitController::class, 'issueCoupon'])->name('admin.members.issue-coupon')->middleware('admin.permission:members.manage');
        Route::delete('/members/{id}/coupons/{memberCouponId}', [MemberBenefitController::class, 'deleteCoupon'])->name('admin.members.delete-coupon')->middleware('admin.permission:members.manage');
        Route::post('/members/{id}/adjust-stamps', [MemberBenefitController::class, 'adjustStamps'])->name('admin.members.adjust-stamps')->middleware('admin.permission:members.manage');
        Route::post('/members/{id}/visits', [AdminMemberActivityController::class, 'recordVisit'])->name('admin.members.record-visit')->middleware('admin.permission:members.manage');
        Route::post('/members/{id}/tags', [AdminMemberActivityController::class, 'attachTag'])->name('admin.members.tags.attach')->middleware('admin.permission:members.manage');
        Route::delete('/members/{id}/tags/{tagId}', [AdminMemberActivityController::class, 'detachTag'])->name('admin.members.tags.detach')->middleware('admin.permission:members.manage');
        Route::put('/members/{id}/memo', [AdminMemberActivityController::class, 'updateMemo'])->name('admin.members.memo')->middleware('admin.permission:members.manage');
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
        Route::post('/reservations/{id}/status', [ReservationStatusController::class, 'update'])->name('admin.reservations.status')->middleware('admin.permission:reservations.update');
        Route::post('/reservations/{id}/complete', [ReservationStatusController::class, 'complete'])->name('admin.reservations.complete')->middleware('admin.permission:reservations.update');

        Route::get('/stamps', [AdminStampController::class, 'stamps'])->name('admin.stamps')->middleware('admin.permission:stamps.view');
        Route::get('/stamps/create', [AdminStampController::class, 'createStamp'])->name('admin.stamps.create')->middleware('admin.permission:stamps.manage');
        Route::get('/stamps/{id}/grant-qr', [StampGrantQrController::class, 'show'])->name('admin.stamps.grant-qr')->middleware('admin.permission:stamps.manage');
        Route::post('/stamps/{id}/grant-qr', [StampGrantQrController::class, 'store'])->name('admin.stamps.grant-qr.store')->middleware('admin.permission:stamps.manage');
        Route::post('/stamps', [AdminStampController::class, 'storeStamp'])->name('admin.stamps.store')->middleware('admin.permission:stamps.manage');
        Route::get('/stamps/{id}/edit', [AdminStampController::class, 'editStamp'])->name('admin.stamps.edit')->middleware('admin.permission:stamps.manage');
        Route::put('/stamps/{id}', [AdminStampController::class, 'updateStamp'])->name('admin.stamps.update')->middleware('admin.permission:stamps.manage');
        Route::delete('/stamps/{id}', [AdminStampController::class, 'deleteStamp'])->name('admin.stamps.delete')->middleware('admin.permission:stamps.manage');

        Route::get('/coupons', [AdminCouponController::class, 'coupons'])->name('admin.coupons')->middleware('admin.permission:coupons.view');
        Route::get('/coupons/create', [AdminCouponController::class, 'createCoupon'])->name('admin.coupons.create')->middleware('admin.permission:coupons.manage');
        Route::post('/coupons', [CouponMutationController::class, 'store'])->name('admin.coupons.store')->middleware('admin.permission:coupons.manage');
        Route::get('/coupons/bulk-issue', [CouponBulkIssueController::class, 'create'])->name('admin.coupons.bulk-issue')->middleware('admin.permission:coupons.manage');
        Route::post('/coupons/bulk-issue', [CouponBulkIssueController::class, 'store'])->name('admin.coupons.bulk-issue.store')->middleware('admin.permission:coupons.manage');
        Route::post('/coupons/quick-create', [CouponBulkIssueController::class, 'quickCreate'])->name('admin.coupons.quick-create')->middleware('admin.permission:coupons.manage');
        Route::post('/coupons/sync-smaregi', [CouponSmaregiSyncController::class, 'pull'])->name('admin.coupons.sync-smaregi')->middleware('admin.permission:coupons.manage');
        Route::post('/coupons/push-smaregi', [CouponSmaregiSyncController::class, 'push'])->name('admin.coupons.push-smaregi')->middleware('admin.permission:coupons.manage');
        Route::post('/coupons/sync-to-smaregi', [CouponSmaregiSyncController::class, 'syncToSmaregi'])->name('admin.coupons.sync-to-smaregi')->middleware('admin.permission:coupons.manage');
        Route::post('/coupons/full-sync-smaregi', [CouponSmaregiSyncController::class, 'sync'])->name('admin.coupons.full-sync-smaregi')->middleware('admin.permission:coupons.manage');
        Route::get('/coupons/ajax/members', [CouponDeliveryController::class, 'searchMembers'])->name('admin.coupons.ajax.members')->middleware('admin.permission:coupons.manage');
        Route::get('/coupons/segment-preview', [CouponDeliveryController::class, 'segmentPreview'])->name('admin.coupons.segment.preview')->middleware('admin.permission:coupons.manage');
        Route::get('/coupons/audience-preview', [CouponDeliveryController::class, 'audiencePreview'])->name('admin.coupons.audience.preview')->middleware('admin.permission:coupons.manage');
        Route::get('/coupons/delivery-preview', [CouponDeliveryController::class, 'preview'])->name('admin.coupons.delivery.preview')->middleware('admin.permission:coupons.manage');
        Route::get('/coupons/delivery-preview-members', [CouponDeliveryController::class, 'previewMembers'])->name('admin.coupons.delivery.preview-members')->middleware('admin.permission:coupons.manage');
        Route::get('/coupons/deliveries/{id}', [CouponDeliveryController::class, 'show'])->name('admin.coupons.deliveries.show')->middleware('admin.permission:coupons.manage');
        Route::get('/coupons/{id}', [AdminCouponController::class, 'showCoupon'])->name('admin.coupons.show')->middleware('admin.permission:coupons.view');
        Route::get('/coupons/{id}/edit', [AdminCouponController::class, 'editCoupon'])->name('admin.coupons.edit')->middleware('admin.permission:coupons.manage');
        Route::get('/coupons/{id}/redemptions', [AdminCouponController::class, 'couponRedemptions'])->name('admin.coupons.redemptions')->middleware('admin.permission:coupons.view');
        Route::put('/coupons/{id}', [CouponMutationController::class, 'update'])->name('admin.coupons.update')->middleware('admin.permission:coupons.manage');
        Route::delete('/coupons/{id}', [CouponMutationController::class, 'destroy'])->name('admin.coupons.delete')->middleware('admin.permission:coupons.manage');

        Route::get('/announcements', [AdminAnnouncementController::class, 'announcements'])->name('admin.announcements')->middleware('admin.permission:announcements.view');
        Route::get('/announcements/create', [AdminAnnouncementController::class, 'createAnnouncement'])->name('admin.announcements.create')->middleware('admin.permission:announcements.manage');
        Route::get('/announcements/delivery', [AnnouncementDeliveryController::class, 'create'])->name('admin.announcements.delivery.create')->middleware(['admin.permission:announcements.manage', 'admin.plan:announcement_delivery']);
        Route::post('/announcements/delivery', [AnnouncementDeliveryController::class, 'store'])->name('admin.announcements.delivery.store')->middleware(['admin.permission:announcements.manage', 'admin.plan:announcement_delivery']);
        Route::get('/announcements/ajax/members', [AnnouncementDeliveryController::class, 'searchMembers'])->name('admin.announcements.ajax.members')->middleware('admin.permission:announcements.view');
        Route::get('/announcements/segment-preview', [AnnouncementDeliveryController::class, 'segmentPreview'])->name('admin.announcements.segment.preview')->middleware('admin.permission:announcements.view');
        Route::get('/announcements/audience-preview', [AnnouncementDeliveryController::class, 'audiencePreview'])->name('admin.announcements.audience.preview')->middleware('admin.permission:announcements.view');
        Route::get('/announcements/{id}/reads', [AdminAnnouncementController::class, 'announcementReads'])->name('admin.announcements.reads')->middleware('admin.permission:announcements.view');
        Route::get('/announcements/{id}/edit', [AdminAnnouncementController::class, 'editAnnouncement'])->name('admin.announcements.edit')->middleware('admin.permission:announcements.manage');
        Route::put('/announcements/{id}', [AdminAnnouncementController::class, 'updateAnnouncement'])->name('admin.announcements.update')->middleware('admin.permission:announcements.manage');
        Route::delete('/announcements/{id}', [AdminAnnouncementController::class, 'deleteAnnouncement'])->name('admin.announcements.delete')->middleware('admin.permission:announcements.manage');

        Route::get('/after-registration-deliveries', [AdminAfterRegistrationDeliveryController::class, 'index'])->name('admin.after-registration-deliveries.index')->middleware('admin.permission:announcements.view');
        Route::get('/after-registration-deliveries/create', [AdminAfterRegistrationDeliveryController::class, 'create'])->name('admin.after-registration-deliveries.create')->middleware('admin.permission:announcements.manage');
        Route::post('/after-registration-deliveries', [AfterRegistrationDeliveryMutationController::class, 'store'])->name('admin.after-registration-deliveries.store')->middleware('admin.permission:announcements.manage');
        Route::get('/after-registration-deliveries/{id}/edit', [AdminAfterRegistrationDeliveryController::class, 'edit'])->name('admin.after-registration-deliveries.edit')->middleware('admin.permission:announcements.manage');
        Route::put('/after-registration-deliveries/{id}', [AfterRegistrationDeliveryMutationController::class, 'update'])->name('admin.after-registration-deliveries.update')->middleware('admin.permission:announcements.manage');
        Route::delete('/after-registration-deliveries/{id}', [AfterRegistrationDeliveryMutationController::class, 'destroy'])->name('admin.after-registration-deliveries.destroy')->middleware('admin.permission:announcements.manage');

        Route::get('/queue', [AdminQueueController::class, 'queue'])->name('admin.queue')->middleware('admin.permission:queue.view');
        Route::post('/queue/{id}/status', [AdminQueueController::class, 'updateQueueStatus'])->name('admin.queue.status')->middleware('admin.permission:queue.update');

        Route::get('/settings', [SettingsIndexController::class, 'index'])->name('admin.settings.index');
        Route::get('/settings/basic', [SettingsBasicController::class, 'index'])->name('admin.settings.basic')->middleware('admin.permission:settings.view');
        Route::post('/settings/basic', [SettingsBasicController::class, 'update'])->name('admin.settings.basic.update')->middleware('admin.permission:settings.manage');
        Route::post('/settings/store-app-credential', [StoreAppCredentialController::class, 'store'])->name('admin.settings.store-app-credential.store')->middleware('admin.permission:settings.manage');
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
        Route::get('/settings/liff-connection', [SettingsLiffConnectionController::class, 'index'])->name('admin.settings.liff-connection')->middleware('admin.permission:settings.view');
        Route::post('/settings/liff-connection', [SettingsLiffConnectionController::class, 'update'])->name('admin.settings.liff-connection.update')->middleware('admin.permission:settings.manage');
        Route::get('/settings/liff-designer', [SettingsLiffDesignerController::class, 'index'])->name('admin.settings.liff-designer')->middleware(['admin.permission:liff_designer.manage', 'admin.plan:liff_designer_advanced']);
        Route::post('/settings/liff-designer', [SettingsLiffDesignerController::class, 'update'])->name('admin.settings.liff-designer.update')->middleware(['admin.permission:liff_designer.manage', 'admin.plan:liff_designer_advanced']);
        Route::get('/settings/reservations', [ReservationsController::class, 'index'])->name('admin.settings.reservations')->middleware('admin.permission:reservations.view');
        Route::post('/settings/reservations', [ReservationsController::class, 'update'])->name('admin.settings.reservations.update')->middleware('admin.permission:settings.manage');
        Route::post('/settings/reservations/courses', [ReservationsController::class, 'storeCourse'])->name('admin.settings.reservations.courses.store')->middleware('admin.permission:settings.manage');
        Route::put('/settings/reservations/courses/{id}', [ReservationsController::class, 'updateCourse'])->name('admin.settings.reservations.courses.update')->middleware('admin.permission:settings.manage');
        Route::get('/settings/staff', [SettingsStaffController::class, 'index'])->name('admin.settings.staff');
        Route::post('/settings/staff', [StaffMutationController::class, 'store'])->name('admin.settings.staff.store');
        Route::put('/settings/staff/{id}', [StaffMutationController::class, 'update'])->name('admin.settings.staff.update');
        Route::get('/settings/queue', [QueueController::class, 'index'])->name('admin.settings.queue')->middleware('admin.permission:queue.view');
        Route::post('/settings/queue', [QueueController::class, 'update'])->name('admin.settings.queue.update')->middleware('admin.permission:settings.manage');
        Route::get('/settings/coupons', [CouponsController::class, 'index'])->name('admin.settings.coupons')->middleware('admin.permission:coupons.view');
        Route::post('/settings/coupons', [CouponsController::class, 'update'])->name('admin.settings.coupons.update')->middleware('admin.permission:settings.manage');
        Route::get('/settings/lottery', [SettingsLotteryController::class, 'index'])->name('admin.settings.lottery')->middleware(['admin.permission:coupons.view', 'admin.plan:lottery']);
        Route::post('/settings/lottery', [SettingsLotteryController::class, 'update'])->name('admin.settings.lottery.update')->middleware(['admin.permission:coupons.manage', 'admin.plan:lottery']);
        Route::get('/settings/announcements', [AnnouncementsController::class, 'index'])->name('admin.settings.announcements')->middleware('admin.permission:announcements.view');
        Route::post('/settings/announcements', [AnnouncementsController::class, 'update'])->name('admin.settings.announcements.update')->middleware('admin.permission:settings.manage');
        Route::get('/settings/existing-member-link', [SettingsExistingMemberLinkController::class, 'index'])->name('admin.settings.existing-member-link');
        Route::post('/settings/existing-member-link', [SettingsExistingMemberLinkController::class, 'update'])->name('admin.settings.existing-member-link.update');
        Route::post('/settings/existing-member-link/connect', [SettingsExistingMemberLinkController::class, 'store'])->name('admin.settings.existing-member-link.store');
        Route::get('/settings/profile', [ProfileController::class, 'index'])->name('admin.settings.profile')->middleware('admin.permission:settings.view');
        Route::post('/settings/profile', [ProfileController::class, 'update'])->name('admin.settings.profile.update')->middleware('admin.permission:settings.manage');
        Route::get('/settings/wallet', [WalletController::class, 'index'])->name('admin.settings.wallet')->middleware(['admin.permission:settings.view', 'admin.plan:wallet']);
        Route::post('/settings/wallet', [WalletController::class, 'update'])->name('admin.settings.wallet.update')->middleware(['admin.permission:settings.manage', 'admin.plan:wallet']);

        Route::get('/analytics', [AdminAnalyticsController::class, 'analytics'])->name('admin.analytics')->middleware(['admin.permission:analytics.view', 'admin.plan:analytics_advanced']);
        Route::get('/analysis-deliveries', [AdminAnalysisDeliveryController::class, 'index'])->name('admin.analysis-deliveries')->middleware(['admin.permission:analytics.view', 'admin.plan:analytics_advanced']);
        Route::post('/analysis-deliveries/settings', [AdminAnalysisDeliveryController::class, 'updateSettings'])->name('admin.analysis-deliveries.settings')->middleware(['admin.permission:settings.manage', 'admin.permission:coupons.manage', 'admin.plan:analytics_advanced']);
        Route::post('/analysis-deliveries/token', [AdminAnalysisDeliveryController::class, 'issueToken'])->name('admin.analysis-deliveries.token')->middleware(['admin.permission:settings.manage', 'admin.permission:coupons.manage', 'admin.plan:analytics_advanced']);
        Route::delete('/analysis-deliveries/token/{token}', [AdminAnalysisDeliveryController::class, 'revokeToken'])->name('admin.analysis-deliveries.token.revoke')->middleware(['admin.permission:settings.manage', 'admin.permission:coupons.manage', 'admin.plan:analytics_advanced']);
        Route::post('/analysis-deliveries', [AdminAnalysisDeliveryController::class, 'store'])->name('admin.analysis-deliveries.store')->middleware(['admin.permission:coupons.manage', 'admin.plan:analytics_advanced']);
        Route::get('/help', [AdminHelpController::class, 'index'])->name('admin.help');
        Route::get('/help/{slug}', [AdminHelpController::class, 'show'])->name('admin.help.show');
        Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->name('admin.audit-logs.index')->middleware('admin.permission:audit_logs.view');
        Route::get('/audit-logs/{id}', [AdminAuditLogController::class, 'show'])->name('admin.audit-logs.show')->middleware('admin.permission:audit_logs.view');

        Route::get('/gallery/categories', [GalleryCategoryController::class, 'index'])->name('admin.gallery.categories')->middleware('admin.permission:gallery.view');
        Route::post('/gallery/categories', [GalleryCategoryController::class, 'store'])->name('admin.gallery.categories.store')->middleware('admin.permission:gallery.manage');
        Route::put('/gallery/categories/{id}', [GalleryCategoryController::class, 'update'])->name('admin.gallery.categories.update')->middleware('admin.permission:gallery.manage');
        Route::delete('/gallery/categories/{id}', [GalleryCategoryController::class, 'delete'])->name('admin.gallery.categories.delete')->middleware('admin.permission:gallery.manage');
        Route::get('/gallery/items', [GalleryItemController::class, 'index'])->name('admin.gallery.items')->middleware('admin.permission:gallery.view');
        Route::post('/gallery/items', [GalleryItemMutationController::class, 'store'])->name('admin.gallery.items.store')->middleware('admin.permission:gallery.manage');
        Route::put('/gallery/items/{id}', [GalleryItemMutationController::class, 'update'])->name('admin.gallery.items.update')->middleware('admin.permission:gallery.manage');
        Route::delete('/gallery/items/{id}', [GalleryItemMutationController::class, 'destroy'])->name('admin.gallery.items.delete')->middleware('admin.permission:gallery.manage');
    });
});

// ========== システム管理画面（システム管理者のみ） ==========
Route::prefix('system-admin')->middleware(['web', 'auth:admin', 'system.admin', 'admin.audit'])->group(function () {
    Route::post('/logout', [LogoutController::class, '__invoke'])->name('system-admin.logout');

    Route::get('/dashboard', [SystemAdminDashboardController::class, 'dashboard'])->name('system-admin.dashboard');
    Route::get('/projects', [AdminProjectController::class, 'index'])->name('system-admin.projects.index');
    Route::get('/projects/input-home', fn () => redirect()->route('onboarding.create'))->name('system-admin.projects.input-home');
    Route::get('/projects/create', fn () => redirect()->route('onboarding.create'))->name('system-admin.projects.create');
    Route::post('/projects', [ProjectMutationController::class, 'store'])->name('system-admin.projects.store');
    Route::get('/projects/{id}/edit', [AdminProjectController::class, 'edit'])->name('system-admin.projects.edit');
    Route::put('/projects/{id}', [ProjectMutationController::class, 'update'])->name('system-admin.projects.update');

    Route::get('/accounts', [AccountIndexController::class, '__invoke'])->name('system-admin.accounts.index');
    Route::get('/accounts/create', [AccountFormController::class, 'create'])->name('system-admin.accounts.create');
    Route::post('/accounts', [AccountStoreController::class, '__invoke'])->name('system-admin.accounts.store');
    Route::get('/accounts/{id}/edit', [AccountFormController::class, 'edit'])->name('system-admin.accounts.edit');
    Route::put('/accounts/{id}', [AccountUpdateController::class, '__invoke'])->name('system-admin.accounts.update');
    Route::delete('/accounts/{id}', [AccountDestroyController::class, '__invoke'])->name('system-admin.accounts.destroy');

    Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->name('system-admin.audit-logs.index');
    Route::get('/audit-logs/{id}', [AdminAuditLogController::class, 'show'])->name('system-admin.audit-logs.show');

    Route::get('/notices', [SystemNoticeController::class, 'index'])->name('system-admin.notices.index');
    Route::post('/notices/preview', [SystemNoticeController::class, 'preview'])->name('system-admin.notices.preview');
    Route::post('/notices', [SystemNoticeController::class, 'store'])->name('system-admin.notices.store');
    Route::put('/notices/{id}', [SystemNoticeController::class, 'update'])->name('system-admin.notices.update');
    Route::delete('/notices/{id}', [SystemNoticeController::class, 'destroy'])->name('system-admin.notices.destroy');
    Route::get('/help-articles', [SystemHelpArticleController::class, 'index'])->name('system-admin.help-articles.index');
    Route::get('/help-articles/create', [SystemHelpArticleController::class, 'create'])->name('system-admin.help-articles.create');
    Route::post('/help-articles', [SystemHelpArticleController::class, 'store'])->name('system-admin.help-articles.store');
    Route::get('/help-articles/{id}/edit', [SystemHelpArticleController::class, 'edit'])->name('system-admin.help-articles.edit');
    Route::put('/help-articles/{id}', [SystemHelpArticleController::class, 'update'])->name('system-admin.help-articles.update');
    Route::delete('/help-articles/{id}', [SystemHelpArticleController::class, 'destroy'])->name('system-admin.help-articles.destroy');
});
Route::get('/mobile/payment/return', PaymentReturnController::class)
    ->name('mobile.payment.return');
