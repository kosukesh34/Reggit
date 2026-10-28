<?php

use App\Http\Controllers\Admin\Account\AccountDestroyController;
use App\Http\Controllers\Admin\Account\AccountFormController;
use App\Http\Controllers\Admin\Account\AccountIndexController;
use App\Http\Controllers\Admin\Account\AccountStoreController;
use App\Http\Controllers\Admin\Account\AccountUpdateController;
use App\Http\Controllers\Admin\AcquisitionSourceController;
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
use App\Http\Controllers\Admin\Auth\AdminLoginSecurityController;
use App\Http\Controllers\Admin\Auth\AdminPasskeyEnrollController;
use App\Http\Controllers\Admin\Auth\AdminPasskeyRecoveryController;
use App\Http\Controllers\Admin\Auth\AdminPasskeyRemoteEnrollController;
use App\Http\Controllers\Admin\Auth\AdminPasskeySettingsController;
use App\Http\Controllers\Admin\Auth\AdminPasskeyVerifyController;
use App\Http\Controllers\Admin\Auth\AdminPasswordController;
use App\Http\Controllers\Admin\Auth\LogoutController;
use App\Http\Controllers\Admin\Auth\SystemAdminLoginController;
use App\Http\Controllers\Admin\ChatController as AdminChatController;
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
use App\Http\Controllers\Admin\MemberTicketController as AdminMemberTicketController;
use App\Http\Controllers\Admin\MemberTransferController as AdminMemberTransferController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PointGrantQrController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProductMutationController;
use App\Http\Controllers\Admin\ProjectAppLinkController;
use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Admin\ProjectMutationController;
use App\Http\Controllers\Admin\PromoPopupController as AdminPromoPopupController;
use App\Http\Controllers\Admin\QrController;
use App\Http\Controllers\Admin\QueueController as AdminQueueController;
use App\Http\Controllers\Admin\ReservationController as AdminReservationController;
use App\Http\Controllers\Admin\ReservationStatusController;
use App\Http\Controllers\Admin\RfmController as AdminRfmController;
use App\Http\Controllers\Admin\Settings\AdminDesignController;
use App\Http\Controllers\Admin\Settings\AdvancedController as SettingsAdvancedController;
use App\Http\Controllers\Admin\Settings\AnnouncementsController;
use App\Http\Controllers\Admin\Settings\AppImagesController;
use App\Http\Controllers\Admin\Settings\BasicController as SettingsBasicController;
use App\Http\Controllers\Admin\Settings\CouponsController;
use App\Http\Controllers\Admin\Settings\ExistingMemberLinkController as SettingsExistingMemberLinkController;
use App\Http\Controllers\Admin\Settings\FeaturesController as SettingsFeaturesController;
use App\Http\Controllers\Admin\Settings\HomeController as SettingsHomeController;
use App\Http\Controllers\Admin\Settings\IndexController as SettingsIndexController;
use App\Http\Controllers\Admin\Settings\LiffConnectionController as SettingsLiffConnectionController;
use App\Http\Controllers\Admin\Settings\LiffDesignerController as SettingsLiffDesignerController;
use App\Http\Controllers\Admin\Settings\LotteryController as SettingsLotteryController;
use App\Http\Controllers\Admin\Settings\MemberCardController;
use App\Http\Controllers\Admin\Settings\MemberCodeController;
use App\Http\Controllers\Admin\Settings\MenuController as SettingsMenuController;
use App\Http\Controllers\Admin\Settings\PaymentsController;
use App\Http\Controllers\Admin\Settings\ProfileController;
use App\Http\Controllers\Admin\Settings\QueueController;
use App\Http\Controllers\Admin\Settings\RanksController as SettingsRanksController;
use App\Http\Controllers\Admin\Settings\ReservationsController;
use App\Http\Controllers\Admin\Settings\ShopController as SettingsShopController;
use App\Http\Controllers\Admin\Settings\StaffController as SettingsStaffController;
use App\Http\Controllers\Admin\Settings\StaffMutationController;
use App\Http\Controllers\Admin\Settings\StoreAppCredentialController;
use App\Http\Controllers\Admin\Settings\WalletController;
use App\Http\Controllers\Admin\ShopOrderController;
use App\Http\Controllers\Admin\StampController as AdminStampController;
use App\Http\Controllers\Admin\StampGrantQrController;
use App\Http\Controllers\Admin\StampQrController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\Admin\SurveyController as AdminSurveyController;
use App\Http\Controllers\Admin\SystemAdminDashboardController;
use App\Http\Controllers\Admin\SystemHelpArticleController;
use App\Http\Controllers\Admin\SystemNoticeController;
use App\Http\Controllers\ApiInfoController;
use App\Http\Controllers\AppLoginOpenController;
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
use App\Http\Controllers\Payment\PaymentCardController;
use App\Http\Controllers\Payment\PaymentReturnController;
use App\Http\Controllers\PointGrantOpenController;
use App\Http\Controllers\PublicHelpController;
use App\Http\Controllers\Queue\QueueOpenController;
use App\Http\Controllers\Shop\ShopAuthController;
use App\Http\Controllers\Shop\ShopController;
use App\Http\Controllers\Shop\ShopLineAuthController;
use App\Http\Controllers\Shop\ShopMemberRegistrationController;
use App\Http\Controllers\Shop\ShopPasskeyController;
use App\Http\Controllers\Shop\ShopReceiptController;
use App\Http\Controllers\Smaregi\SmaregiIdpController;
use App\Http\Controllers\Smaregi\SmaregiWebhookController;
use App\Http\Controllers\StampGrantOpenController;
use App\Http\Controllers\WalletAddPageController;
use Illuminate\Support\Facades\Route;
use Laravel\Passkeys\Http\Controllers\PasskeyConfirmationController;
use Laravel\Passkeys\Http\Controllers\PasskeyRegistrationController;

// ========== 公開 ==========
Route::get('/', LandingController::class);
Route::get('/help', [PublicHelpController::class, 'index'])->name('help.index');
Route::get('/help/{slug}', [PublicHelpController::class, 'show'])->name('help.show');
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
Route::post('/existing-member-link/{token}', [ExistingMemberLinkConfirmController::class, 'confirm'])
    ->middleware('throttle:20,1')
    ->name('existing-member-link.complete');
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

// ========== 公開ショップ（PC / 一般ブラウザ） ==========
Route::prefix('web/{project_key}')->middleware(['web', 'bind.project', 'shop.enabled'])->group(function () {
    Route::get('/shop', [ShopController::class, 'show'])->name('shop.show');
    Route::get('/shop/legal', [ShopController::class, 'legal'])->name('shop.legal');
    Route::get('/shop/login', [ShopAuthController::class, 'loginForm'])->name('shop.login');
    Route::post('/shop/logout', [ShopAuthController::class, 'logout'])->name('shop.logout');
    Route::get('/shop/line/start', [ShopLineAuthController::class, 'start'])->name('shop.line.start');
    Route::get('/shop/line/callback', [ShopLineAuthController::class, 'callback'])->name('shop.line.callback');
    Route::post('/shop/email', [ShopAuthController::class, 'sendEmail'])->middleware('throttle:5,1')->name('shop.email.send');
    Route::get('/shop/email/consume', [ShopAuthController::class, 'consumeEmail'])->middleware('throttle:20,1')->name('shop.email.consume');
    Route::post('/shop/app', [ShopAuthController::class, 'loginWithApp'])->middleware('throttle:5,1')->name('shop.app.login');
    Route::get('/shop/passkey/login/options', [ShopPasskeyController::class, 'loginOptions'])->name('shop.passkey.login.options');
    Route::post('/shop/passkey/login', [ShopPasskeyController::class, 'login'])->middleware('throttle:12,1')->name('shop.passkey.login');
    Route::get('/shop/passkey/register/options', [ShopPasskeyController::class, 'registerOptions'])->middleware('auth')->name('shop.passkey.register.options');
    Route::post('/shop/passkey/register', [ShopPasskeyController::class, 'register'])->middleware(['auth', 'throttle:12,1'])->name('shop.passkey.register');
    Route::get('/shop/member/register', [ShopMemberRegistrationController::class, 'form'])->name('shop.member.register');
    Route::post('/shop/member/register', [ShopMemberRegistrationController::class, 'store'])->middleware('throttle:10,1')->name('shop.member.register.store');
});

// 独自ショップドメイン（Host → project）。LIFF / 管理画面は対象外。
// 領収書は署名つき URL で開く（ショップ停止後も過去の注文分は表示できる）
Route::get('/web/{project_key}/shop/orders/{order}/receipt', ShopReceiptController::class)
    ->whereNumber('order')->middleware(['web', 'bind.project', 'signed', 'throttle:60,1'])->name('shop.receipt');

Route::middleware(['web', 'shop.domain', 'shop.enabled'])->group(function () {
    Route::get('/shop', [ShopController::class, 'show'])->name('shop.domain.show');
    Route::get('/shop/legal', [ShopController::class, 'legal'])->name('shop.domain.legal');
    Route::get('/shop/login', [ShopAuthController::class, 'loginForm'])->name('shop.domain.login');
    Route::post('/shop/logout', [ShopAuthController::class, 'logout'])->name('shop.domain.logout');
    Route::get('/shop/line/start', [ShopLineAuthController::class, 'start'])->name('shop.domain.line.start');
    Route::get('/shop/line/callback', [ShopLineAuthController::class, 'callback'])->name('shop.domain.line.callback');
    Route::post('/shop/email', [ShopAuthController::class, 'sendEmail'])->middleware('throttle:5,1')->name('shop.domain.email.send');
    Route::get('/shop/email/consume', [ShopAuthController::class, 'consumeEmail'])->middleware('throttle:20,1')->name('shop.domain.email.consume');
    Route::post('/shop/app', [ShopAuthController::class, 'loginWithApp'])->middleware('throttle:5,1')->name('shop.domain.app.login');
    Route::get('/shop/passkey/login/options', [ShopPasskeyController::class, 'loginOptions'])->name('shop.domain.passkey.login.options');
    Route::post('/shop/passkey/login', [ShopPasskeyController::class, 'login'])->middleware('throttle:12,1')->name('shop.domain.passkey.login');
    Route::get('/shop/passkey/register/options', [ShopPasskeyController::class, 'registerOptions'])->middleware('auth')->name('shop.domain.passkey.register.options');
    Route::post('/shop/passkey/register', [ShopPasskeyController::class, 'register'])->middleware(['auth', 'throttle:12,1'])->name('shop.domain.passkey.register');
    Route::get('/shop/member/register', [ShopMemberRegistrationController::class, 'form'])->name('shop.domain.member.register');
    Route::post('/shop/member/register', [ShopMemberRegistrationController::class, 'store'])->middleware('throttle:10,1')->name('shop.domain.member.register.store');
});

// アプリのメールログイン用の中継ページ（メール内リンク → reggit://login）
Route::get('/app-login', AppLoginOpenController::class)->name('app-login.open');

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

Route::get('/points/claim/{project_key}/{token}', PointGrantOpenController::class)->where('token', '[0-9a-f]{48}')->middleware(['bind.project', 'throttle:60,1'])->name('points.grant.open');

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
Route::get('/admin/change-password', [AdminPasswordController::class, 'edit'])->middleware(['auth:admin', 'admin.auth.session', 'admin.passkey'])->name('admin.password.edit');
Route::put('/admin/change-password', [AdminPasswordController::class, 'change'])->middleware(['auth:admin', 'admin.auth.session', 'admin.passkey', 'throttle:5,1'])->name('admin.password.change');
Route::get('/admin/login-security/current-ip', [AdminLoginSecurityController::class, 'currentIp'])->middleware(['auth:admin', 'admin.auth.session', 'admin.passkey', 'throttle:30,1'])->name('admin.login-security.current-ip');
Route::put('/admin/login-security', [AdminLoginSecurityController::class, 'update'])->middleware(['auth:admin', 'admin.auth.session', 'admin.passkey', 'throttle:10,1'])->name('admin.login-security.update');

Route::get('/admin/login', [AdminLoginController::class, 'show'])->name('admin.login');
Route::get('/admin/login/form', [AdminLoginController::class, 'showLoginForm'])->name('admin.login.form');
Route::post('/admin/login', [AdminLoginController::class, 'login'])->name('admin.login.submit')->middleware('throttle:admin-login');
Route::get('/admin/{project_key}/login', [AdminLoginController::class, 'showProject'])->name('admin.project.login');
Route::post('/admin/{project_key}/login', [AdminLoginController::class, 'login'])->name('admin.project.login.submit')->middleware('throttle:admin-login');

Route::get('/system-admin/login', [SystemAdminLoginController::class, 'show'])->name('system-admin.login');
Route::post('/system-admin/login', [SystemAdminLoginController::class, 'login'])->name('system-admin.login.submit')->middleware('throttle:admin-login');

Route::get('/admin/passkey/remote-enroll/confirm', [AdminPasskeyRemoteEnrollController::class, 'confirm'])
    ->middleware(['web', 'throttle:10,1'])->name('admin.passkey.remote-enroll.confirm');

Route::middleware(['web', 'auth:admin', 'admin.auth.session'])->group(function () {
    Route::get('/admin/passkey/verify', AdminPasskeyVerifyController::class)->name('admin.passkey.verify');
    Route::get('/admin/passkey/enroll', AdminPasskeyEnrollController::class)->name('admin.passkey.enroll');
    Route::post('/admin/passkey/recovery/email', [AdminPasskeyRecoveryController::class, 'send'])
        ->middleware('throttle:5,1')->name('admin.passkey.recovery.email');
    Route::get('/admin/passkey/recovery/confirm', [AdminPasskeyRecoveryController::class, 'confirm'])
        ->middleware('throttle:10,1')->name('admin.passkey.recovery.confirm');
    Route::get('/admin/passkeys', AdminPasskeySettingsController::class)->middleware('admin.passkey')->name('admin.passkey.settings');
    Route::post('/admin/passkeys/remote-enroll', [AdminPasskeyRemoteEnrollController::class, 'create'])
        ->middleware(['admin.passkey', 'throttle:10,1'])->name('admin.passkey.remote-enroll.create');

    Route::get('/admin/passkeys/confirm/options', [PasskeyConfirmationController::class, 'index'])
        ->middleware('throttle:6,1')->name('passkey.confirm-options');
    Route::post('/admin/passkeys/confirm', [PasskeyConfirmationController::class, 'store'])
        ->middleware('throttle:6,1')->name('passkey.confirm');
    Route::get('/admin/passkeys/options', [PasskeyRegistrationController::class, 'index'])
        ->middleware('throttle:6,1')->name('passkey.registration-options');
    Route::post('/admin/passkeys', [PasskeyRegistrationController::class, 'store'])
        ->middleware('throttle:6,1')->name('passkey.store');
    Route::delete('/admin/passkeys/{passkey}', [PasskeyRegistrationController::class, 'destroy'])
        ->middleware('admin.passkey')->name('passkey.destroy');
});

// /admin のみのアクセス時はアクセス可能な先頭プロジェクトへリダイレクト
Route::get('/admin', AdminRootController::class)->middleware('web')->name('admin.root');

// ========== 店舗管理画面（project_key 必須） ==========
Route::prefix('admin')->middleware(['web', 'auth:admin', 'admin.auth.session', 'admin.passkey', 'bind.admin.project', 'admin.audit'])->group(function () {
    Route::post('/logout', [LogoutController::class, '__invoke'])->name('admin.logout')->withoutMiddleware('bind.admin.project');

    Route::prefix('{project_key}')->group(function () {
        Route::post('/qr-codes/stamps', [StampQrController::class, 'store'])->name('admin.qr-codes.stamps.store')->middleware('admin.permission:stamps.manage');
        Route::post('/qr-codes/points', [PointGrantQrController::class, 'store'])->name('admin.qr-codes.points.store')->middleware('admin.permission:members.manage');
        Route::get('/qr-codes', QrController::class)->name('admin.qr-codes')->middleware('admin.permission:members.view,members.manage,stamps.manage');
        Route::get('/stores', [StoreController::class, 'index'])->name('admin.stores.index');
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
        Route::get('/shop/orders', [ShopOrderController::class, 'index'])->name('admin.shop.orders')->middleware('admin.permission:orders.view');
        Route::get('/shop/orders/{id}', [ShopOrderController::class, 'detail'])->name('admin.shop.orders.detail')->middleware('admin.permission:orders.view');
        Route::post('/shop/orders/{id}/status', [ShopOrderController::class, 'updateStatus'])->name('admin.shop.orders.status')->middleware('admin.permission:orders.update');
        Route::post('/shop/orders/{id}/complete', [ShopOrderController::class, 'complete'])->name('admin.shop.orders.complete')->middleware('admin.permission:orders.update');
        Route::post('/shop/orders/{id}/refund', [ShopOrderController::class, 'refund'])->name('admin.shop.orders.refund')->middleware('admin.permission:orders.update');

        Route::get('/products', [AdminProductController::class, 'products'])->name('admin.products')->middleware('admin.permission:products.view');
        Route::post('/products/import-smaregi', [AdminProductController::class, 'importSmaregi'])->name('admin.products.import-smaregi')->middleware('admin.permission:products.manage');
        Route::get('/products/create', [AdminProductController::class, 'createProduct'])->name('admin.products.create')->middleware('admin.permission:products.manage');
        Route::post('/products', [ProductMutationController::class, 'store'])->name('admin.products.store')->middleware('admin.permission:products.manage');
        Route::get('/products/{id}/edit', [AdminProductController::class, 'editProduct'])->name('admin.products.edit')->middleware('admin.permission:products.manage');
        Route::put('/products/{id}', [ProductMutationController::class, 'update'])->name('admin.products.update')->middleware('admin.permission:products.manage');
        Route::delete('/products/{id}', [ProductMutationController::class, 'destroy'])->name('admin.products.delete')->middleware('admin.permission:products.manage');

        Route::get('/chats', [AdminChatController::class, 'index'])->name('admin.chats')->middleware('admin.permission:chat.manage');
        Route::post('/chats/token', [AdminChatController::class, 'token'])->name('admin.chats.token')->middleware(['admin.permission:chat.manage', 'throttle:30,1']);
        Route::post('/chats/{member}/notify', [AdminChatController::class, 'notify'])->whereNumber('member')->name('admin.chats.notify')->middleware(['admin.permission:chat.manage', 'throttle:60,1']);
        Route::get('/chats/members', [AdminChatController::class, 'members'])->name('admin.chats.members')->middleware('admin.permission:chat.manage');
        Route::post('/chats/{member}/read', [AdminChatController::class, 'read'])->whereNumber('member')->name('admin.chats.read')->middleware(['admin.permission:chat.manage', 'throttle:120,1']);
        // パスに「/stamps」を含めない（支店管理者のプロジェクト共通設定の保護に当たらないよう、会員詳細の adjust-stamps と同じ扱い）
        Route::post('/chats/{member}/grant-stamps', [AdminChatController::class, 'grantStamps'])->whereNumber('member')->name('admin.chats.stamps')->middleware(['admin.permission:chat.manage', 'admin.permission:members.manage', 'throttle:30,1']);
        Route::get('/chats/{member}/context', [AdminChatController::class, 'context'])->whereNumber('member')->name('admin.chats.context')->middleware('admin.permission:chat.manage');
        Route::post('/chats/{member}/issue-coupon/{coupon}', [AdminChatController::class, 'issueCoupon'])->whereNumber(['member', 'coupon'])->name('admin.chats.coupon')->middleware(['admin.permission:chat.manage', 'admin.permission:members.manage', 'throttle:30,1']);

        Route::get('/members', [MemberIndexController::class, 'index'])->name('admin.members')->middleware('admin.permission:members.view');
        Route::post('/members/{id}/transfer-code', [AdminMemberTransferController::class, 'store'])->name('admin.members.transfer-code')->middleware('admin.permission:members.manage');
        Route::post('/members/{id}/subscriptions/{subscription}/cancel', [AdminMemberTicketController::class, 'cancelSubscription'])->whereNumber(['id', 'subscription'])->name('admin.members.subscriptions.cancel')->middleware('admin.permission:members.manage');
        Route::post('/members/{id}/tickets/{ticket}/revoke', [AdminMemberTicketController::class, 'revoke'])->whereNumber(['id', 'ticket'])->name('admin.members.tickets.revoke')->middleware('admin.permission:members.manage');
        Route::post('/members/{id}/tickets/{ticket}/uses/{use}/cancel', [AdminMemberTicketController::class, 'cancelUse'])->whereNumber(['id', 'ticket', 'use'])->name('admin.members.tickets.uses.cancel')->middleware('admin.permission:members.manage');
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
        Route::put('/rfm/settings', [AdminRfmController::class, 'updateSettings'])->name('admin.rfm.settings.update')->middleware('admin.permission:settings.manage');

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
        Route::post('/announcements/audience-groups', [AnnouncementDeliveryController::class, 'storeAudienceGroup'])->name('admin.announcements.audience-groups.store')->middleware(['admin.permission:announcements.manage', 'admin.plan:announcement_delivery']);
        Route::delete('/announcements/audience-groups/{id}', [AnnouncementDeliveryController::class, 'destroyAudienceGroup'])->name('admin.announcements.audience-groups.destroy')->middleware(['admin.permission:announcements.manage', 'admin.plan:announcement_delivery']);
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

        Route::get('/surveys', [AdminSurveyController::class, 'index'])->name('admin.surveys.index')->middleware('admin.permission:announcements.view');
        Route::get('/surveys/create', [AdminSurveyController::class, 'create'])->name('admin.surveys.create')->middleware('admin.permission:announcements.manage');
        Route::post('/surveys', [AdminSurveyController::class, 'store'])->name('admin.surveys.store')->middleware('admin.permission:announcements.manage');
        Route::get('/surveys/{id}', [AdminSurveyController::class, 'show'])->whereNumber('id')->name('admin.surveys.show')->middleware('admin.permission:announcements.view');
        Route::post('/surveys/{id}/duplicate', [AdminSurveyController::class, 'duplicate'])->whereNumber('id')->name('admin.surveys.duplicate')->middleware('admin.permission:announcements.manage');
        Route::get('/surveys/{id}/export', [AdminSurveyController::class, 'export'])->whereNumber('id')->name('admin.surveys.export')->middleware('admin.permission:announcements.view');
        Route::get('/surveys/{id}/edit', [AdminSurveyController::class, 'edit'])->whereNumber('id')->name('admin.surveys.edit')->middleware('admin.permission:announcements.manage');
        Route::put('/surveys/{id}', [AdminSurveyController::class, 'update'])->whereNumber('id')->name('admin.surveys.update')->middleware('admin.permission:announcements.manage');
        Route::delete('/surveys/{id}', [AdminSurveyController::class, 'destroy'])->whereNumber('id')->name('admin.surveys.destroy')->middleware('admin.permission:announcements.manage');

        Route::get('/promo-popups', [AdminPromoPopupController::class, 'index'])->name('admin.promo-popups.index')->middleware('admin.permission:announcements.view');
        Route::get('/promo-popups/create', [AdminPromoPopupController::class, 'create'])->name('admin.promo-popups.create')->middleware('admin.permission:announcements.manage');
        Route::post('/promo-popups', [AdminPromoPopupController::class, 'store'])->name('admin.promo-popups.store')->middleware('admin.permission:announcements.manage');
        Route::post('/promo-popups/{id}/duplicate', [AdminPromoPopupController::class, 'duplicate'])->whereNumber('id')->name('admin.promo-popups.duplicate')->middleware('admin.permission:announcements.manage');
        Route::get('/promo-popups/{id}/edit', [AdminPromoPopupController::class, 'edit'])->whereNumber('id')->name('admin.promo-popups.edit')->middleware('admin.permission:announcements.manage');
        Route::put('/promo-popups/{id}', [AdminPromoPopupController::class, 'update'])->whereNumber('id')->name('admin.promo-popups.update')->middleware('admin.permission:announcements.manage');
        Route::delete('/promo-popups/{id}', [AdminPromoPopupController::class, 'destroy'])->whereNumber('id')->name('admin.promo-popups.destroy')->middleware('admin.permission:announcements.manage');

        Route::get('/acquisition-sources', [AcquisitionSourceController::class, 'index'])->name('admin.acquisition-sources')->middleware('admin.permission:members.view');
        Route::post('/acquisition-sources', [AcquisitionSourceController::class, 'store'])->name('admin.acquisition-sources.store')->middleware('admin.permission:members.manage');
        Route::delete('/acquisition-sources/{id}', [AcquisitionSourceController::class, 'destroy'])->whereNumber('id')->name('admin.acquisition-sources.destroy')->middleware('admin.permission:members.manage');

        Route::get('/queue', [AdminQueueController::class, 'queue'])->name('admin.queue')->middleware('admin.permission:queue.view');
        Route::post('/queue/{id}/status', [AdminQueueController::class, 'updateQueueStatus'])->name('admin.queue.status')->middleware('admin.permission:queue.update');

        Route::get('/settings', [SettingsIndexController::class, 'index'])->name('admin.settings.index');
        Route::get('/settings/basic', [SettingsBasicController::class, 'index'])->name('admin.settings.basic')->middleware('admin.permission:settings.view');
        Route::post('/settings/basic', [SettingsBasicController::class, 'update'])->name('admin.settings.basic.update')->middleware('admin.permission:settings.manage');
        Route::get('/settings/shop', [SettingsShopController::class, 'index'])->name('admin.settings.shop')->middleware('admin.permission:settings.view');
        Route::post('/settings/shop', [SettingsShopController::class, 'update'])->name('admin.settings.shop.update')->middleware('admin.permission:settings.manage');
        Route::post('/settings/store-app-credential', [StoreAppCredentialController::class, 'store'])->name('admin.settings.store-app-credential.store')->middleware('admin.permission:settings.manage');
        Route::get('/settings/payments', [PaymentsController::class, 'index'])->name('admin.settings.payments')->middleware('admin.permission:settings.manage');
        Route::post('/settings/payments', [PaymentsController::class, 'update'])->name('admin.settings.payments.update')->middleware('admin.permission:settings.manage');
        Route::get('/settings/advanced', [SettingsAdvancedController::class, 'index'])->name('admin.settings.advanced')->middleware('admin.permission:settings.view');
        Route::post('/settings/advanced', [SettingsAdvancedController::class, 'update'])->name('admin.settings.advanced.update')->middleware('admin.permission:settings.manage');
        Route::get('/settings/menu', [SettingsMenuController::class, 'index'])->name('admin.settings.menu')->middleware('admin.permission:settings.view');
        Route::post('/settings/menu', [SettingsMenuController::class, 'update'])->name('admin.settings.menu.update')->middleware('admin.permission:settings.manage');
        Route::get('/settings/home', [SettingsHomeController::class, 'index'])->name('admin.settings.home')->middleware('admin.permission:settings.view');
        Route::post('/settings/home', [SettingsHomeController::class, 'update'])->name('admin.settings.home.update')->middleware('admin.permission:settings.manage');
        Route::get('/settings/app-design/branding', [AppImagesController::class, 'index'])->name('admin.settings.app-branding')->middleware('admin.permission:settings.view');
        Route::post('/settings/app-design/branding', [AppImagesController::class, 'update'])->name('admin.settings.app-branding.update')->middleware('admin.permission:settings.manage');
        Route::get('/settings/admin-design', [AdminDesignController::class, 'index'])->name('admin.settings.admin-design')->middleware('admin.permission:settings.view');
        Route::post('/settings/admin-design', [AdminDesignController::class, 'update'])->name('admin.settings.admin-design.update')->middleware('admin.permission:settings.manage');
        Route::get('/settings/app-design/member-card', [MemberCardController::class, 'index'])->name('admin.settings.member-card')->middleware('admin.permission:settings.view');
        Route::post('/settings/app-design/member-card', [MemberCardController::class, 'update'])->name('admin.settings.member-card.update')->middleware('admin.permission:settings.manage');
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
        Route::post('/settings/member-code', [MemberCodeController::class, 'update'])->name('admin.settings.member-code.update')->middleware('admin.permission:settings.manage');
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
Route::prefix('system-admin')->middleware(['web', 'auth:admin', 'admin.auth.session', 'admin.passkey', 'system.admin', 'admin.audit'])->group(function () {
    Route::get('/', fn () => redirect()->route('system-admin.dashboard'));
    Route::post('/logout', [LogoutController::class, '__invoke'])->name('system-admin.logout');

    Route::get('/dashboard', [SystemAdminDashboardController::class, 'dashboard'])->name('system-admin.dashboard');
    Route::get('/projects', [AdminProjectController::class, 'index'])->name('system-admin.projects.index');
    Route::get('/projects/input-home', fn () => redirect()->route('onboarding.create'))->name('system-admin.projects.input-home');
    Route::get('/projects/create', fn () => redirect()->route('onboarding.create'))->name('system-admin.projects.create');
    Route::post('/projects', [ProjectMutationController::class, 'store'])->name('system-admin.projects.store');
    Route::get('/projects/{id}/edit', [AdminProjectController::class, 'edit'])->name('system-admin.projects.edit');
    Route::put('/projects/{id}/app-links', [ProjectAppLinkController::class, 'update'])->name('system-admin.projects.app-links.update');
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
    Route::post('/help-articles/preview', [SystemHelpArticleController::class, 'preview'])->name('system-admin.help-articles.preview');
    Route::get('/help-articles/create', [SystemHelpArticleController::class, 'create'])->name('system-admin.help-articles.create');
    Route::post('/help-articles', [SystemHelpArticleController::class, 'store'])->name('system-admin.help-articles.store');
    Route::get('/help-articles/{id}/preview', [SystemHelpArticleController::class, 'showPreview'])->name('system-admin.help-articles.show-preview');
    Route::get('/help-articles/{id}/edit', [SystemHelpArticleController::class, 'edit'])->name('system-admin.help-articles.edit');
    Route::put('/help-articles/{id}', [SystemHelpArticleController::class, 'update'])->name('system-admin.help-articles.update');
    Route::delete('/help-articles/{id}', [SystemHelpArticleController::class, 'destroy'])->name('system-admin.help-articles.destroy');
});
Route::get('/mobile/payment/return', PaymentReturnController::class)
    ->name('mobile.payment.return');

Route::get('/mobile/payment/card/{order}', PaymentCardController::class)
    ->whereNumber('order')->middleware(['signed', 'bind.project', 'throttle:60,1'])->name('mobile.payment.card');
