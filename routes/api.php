<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\Api\Kitchen\KitchenDeviceController;
use App\Http\Controllers\Api\Kitchen\KitchenOrderController;
use App\Http\Controllers\Api\V1\AnnouncementCancelledIdsController as ApiV1AnnouncementCancelledIdsController;
use App\Http\Controllers\Api\V1\AnnouncementController as ApiV1AnnouncementController;
use App\Http\Controllers\Api\V1\AppConfigController;
use App\Http\Controllers\Api\V1\CouponController as ApiV1CouponController;
use App\Http\Controllers\Api\V1\Member\MemberMeController as ApiV1MemberMeController;
use App\Http\Controllers\Api\V1\Member\MemberPointCardController as ApiV1MemberPointCardController;
use App\Http\Controllers\Api\V1\Member\MemberPointHistoryController as ApiV1MemberPointHistoryController;
use App\Http\Controllers\Api\V1\Member\MemberPointsController as ApiV1MemberPointsController;
use App\Http\Controllers\Api\V1\Member\MemberWithdrawController as ApiV1MemberWithdrawController;
use App\Http\Controllers\Api\V1\OrderController as ApiV1OrderController;
use App\Http\Controllers\Api\V1\PaymentController as ApiV1PaymentController;
use App\Http\Controllers\Api\V1\ProductController as ApiV1ProductController;
use App\Http\Controllers\Api\V1\ProjectListController as ApiV1ProjectListController;
use App\Http\Controllers\Api\V1\Reservation\ReservationIndexController as ApiV1ReservationIndexController;
use App\Http\Controllers\Api\V1\StampController as ApiV1StampController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\DeviceRegisterController;
use App\Http\Controllers\DeviceSessionController;
use App\Http\Controllers\Diagnostics\ClientDebugController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\LineWebhookController;
use App\Http\Controllers\Member\GoogleWalletUrlController;
use App\Http\Controllers\Member\MemberAddPointsController;
use App\Http\Controllers\Member\MemberLotteryController;
use App\Http\Controllers\Member\MemberMeController;
use App\Http\Controllers\Member\MemberPointCardController;
use App\Http\Controllers\Member\MemberPointHistoryController;
use App\Http\Controllers\Member\MemberPointsController;
use App\Http\Controllers\Member\MemberRegisterController;
use App\Http\Controllers\Member\MemberUpdateController;
use App\Http\Controllers\Member\MemberWalletSummaryController;
use App\Http\Controllers\Member\MemberWithdrawController;
use App\Http\Controllers\Member\WalletPassController;
use App\Http\Controllers\Member\WalletPassUrlController;
use App\Http\Controllers\Onboarding\OnboardingProjectStoreController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Payment\PaymentConfirmController;
use App\Http\Controllers\Payment\PaymentCreateIntentController;
use App\Http\Controllers\Payment\PaymentWebhookController;
use App\Http\Controllers\PostalCodeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Queue\QueueCallNextController;
use App\Http\Controllers\Queue\QueueCancelController;
use App\Http\Controllers\Queue\QueueEnterController;
use App\Http\Controllers\Queue\QueueListController;
use App\Http\Controllers\Queue\QueueMeController;
use App\Http\Controllers\Queue\QueueQrStatusController;
use App\Http\Controllers\Queue\QueueRecallController;
use App\Http\Controllers\Queue\QueueStatusController;
use App\Http\Controllers\Queue\QueueStoreController;
use App\Http\Controllers\Reservation\ReservationAvailableDatesController;
use App\Http\Controllers\Reservation\ReservationCancelController;
use App\Http\Controllers\Reservation\ReservationIndexController;
use App\Http\Controllers\Reservation\ReservationStoreController;
use App\Http\Controllers\Reservation\ReservationUpdateController;
use App\Http\Controllers\Smaregi\SmaregiIdpController;
use App\Http\Controllers\Smaregi\SmaregiWebhookController;
use App\Http\Controllers\StampController;
use App\Http\Controllers\Wallet\AppleWalletPasskitController;
use App\Http\Middleware\AuthenticateApiToken;
use App\Http\Middleware\AuthenticateKitchenToken;
use App\Http\Middleware\CheckApiProject;
use Illuminate\Support\Facades\Route;

Route::match(['get', 'post'], '/line/webhook', [LineWebhookController::class, 'handle']);

Route::prefix('kitchen/{project_key}')->middleware([AuthenticateKitchenToken::class])->group(function () {
    Route::get('/orders', [KitchenOrderController::class, 'index']);
    Route::post('/orders/{id}/status', [KitchenOrderController::class, 'updateStatus']);
    Route::post('/devices', [KitchenDeviceController::class, 'store']);
    Route::delete('/devices', [KitchenDeviceController::class, 'destroy']);
});

// スマレジ・プラットフォームAPI 連携（利用者契約通知・Webhook）
// 公式: HTTPS POST / JSON。接続確認で GET/HEAD が来る場合があるため許可。
Route::match(['get', 'head', 'post'], '/smaregi/idp', [SmaregiIdpController::class, '__invoke'])->name('smaregi.idp');
Route::match(['get', 'head', 'post'], '/smaregi/webhook', [SmaregiWebhookController::class, '__invoke'])
    ->middleware('verify.smaregi.webhook')
    ->name('smaregi.webhook');

// 公開オンボーディング（将来切り出し可能な JSON API）
Route::post('/onboarding/projects', OnboardingProjectStoreController::class)
    ->middleware('throttle:onboarding')
    ->name('api.onboarding.projects.store');

// API v1（Bearer トークン認証・モバイルアプリ用）※従来: トークンの project でコンテキスト設定
Route::prefix('v1')->middleware([AuthenticateApiToken::class])->group(function () {
    Route::get('/products', [ApiV1ProductController::class, 'index']);
    Route::get('/products/{id}', [ApiV1ProductController::class, 'show']);
    Route::post('/orders', [ApiV1OrderController::class, 'store']);
    Route::post('/orders/{id}/cancel-unpaid', [ApiV1OrderController::class, 'cancelUnpaid']);
    Route::get('/orders', [ApiV1OrderController::class, 'index']);
    Route::get('/orders/{id}', [ApiV1OrderController::class, 'show']);
    Route::get('/members/me', [ApiV1MemberMeController::class, '__invoke']);
    Route::get('/members/points', [ApiV1MemberPointsController::class, '__invoke']);
    Route::get('/members/points/card', [ApiV1MemberPointCardController::class, '__invoke']);
    Route::get('/members/points/history', [ApiV1MemberPointHistoryController::class, '__invoke']);
    Route::post('/members/withdraw', [ApiV1MemberWithdrawController::class, '__invoke']);
    Route::get('/members/me/coupons', [ApiV1CouponController::class, 'myCoupons']);
    Route::post('/members/me/coupons/{id}/use', [ApiV1CouponController::class, 'use']);
    Route::get('/members/me/lottery', [MemberLotteryController::class, 'index']);
    Route::post('/members/me/lottery/{id}/play', [MemberLotteryController::class, 'play']);
    Route::post('/members/me/lottery/{id}/notify-win', [MemberLotteryController::class, 'notifyWin']);
    Route::get('/members/me/wallet-summary', MemberWalletSummaryController::class);
    Route::get('/stamps', [ApiV1StampController::class, 'index']);
    Route::get('/announcements', [ApiV1AnnouncementController::class, 'index']);
    Route::post('/announcements/{id}/read', [ApiV1AnnouncementController::class, 'markRead']);
    Route::get('/reservations', [ApiV1ReservationIndexController::class, '__invoke']);
    Route::post('/payment/create-intent', [ApiV1PaymentController::class, 'createPaymentIntent']);
    Route::post('/payment/confirm', [ApiV1PaymentController::class, 'confirmPayment']);
});

// API v1（SaaS: URL に project_key を含む）api/v1/{project_key}/...
Route::prefix('v1')->middleware([CheckApiProject::class, AuthenticateApiToken::class])->group(function () {
    Route::get('/{project_key}/products', [ApiV1ProductController::class, 'index']);
    Route::get('/{project_key}/products/{id}', [ApiV1ProductController::class, 'show']);
    Route::post('/{project_key}/orders', [ApiV1OrderController::class, 'store']);
    Route::post('/{project_key}/orders/{id}/cancel-unpaid', [ApiV1OrderController::class, 'cancelUnpaid']);
    Route::get('/{project_key}/orders', [ApiV1OrderController::class, 'index']);
    Route::get('/{project_key}/orders/{id}', [ApiV1OrderController::class, 'show']);
    Route::get('/{project_key}/members/me', [ApiV1MemberMeController::class, '__invoke']);
    Route::get('/{project_key}/members/points', [ApiV1MemberPointsController::class, '__invoke']);
    Route::get('/{project_key}/members/points/card', [ApiV1MemberPointCardController::class, '__invoke']);
    Route::get('/{project_key}/members/points/history', [ApiV1MemberPointHistoryController::class, '__invoke']);
    Route::post('/{project_key}/members/withdraw', [ApiV1MemberWithdrawController::class, '__invoke']);
    Route::get('/{project_key}/members/me/coupons', [ApiV1CouponController::class, 'myCoupons']);
    Route::post('/{project_key}/members/me/coupons/{id}/use', [ApiV1CouponController::class, 'use']);
    Route::get('/{project_key}/members/me/lottery', [MemberLotteryController::class, 'index']);
    Route::post('/{project_key}/members/me/lottery/{id}/play', [MemberLotteryController::class, 'play']);
    Route::post('/{project_key}/members/me/lottery/{id}/notify-win', [MemberLotteryController::class, 'notifyWin']);
    Route::get('/{project_key}/members/me/wallet-summary', MemberWalletSummaryController::class);
    Route::get('/{project_key}/stamps', [ApiV1StampController::class, 'index']);
    Route::get('/{project_key}/announcements', [ApiV1AnnouncementController::class, 'index']);
    Route::get('/{project_key}/announcements/cancelled-ids', [ApiV1AnnouncementController::class, 'cancelledIds']);
    Route::post('/{project_key}/announcements/{id}/read', [ApiV1AnnouncementController::class, 'markRead']);
    Route::get('/{project_key}/reservations', [ApiV1ReservationIndexController::class, '__invoke']);
    Route::post('/{project_key}/payment/create-intent', [ApiV1PaymentController::class, 'createPaymentIntent']);
    Route::post('/{project_key}/payment/confirm', [ApiV1PaymentController::class, 'confirmPayment']);
});

// ネイティブアプリ設定（認証不要・project_key クエリで取得）
Route::get('/v1/app-config', AppConfigController::class);
Route::get('/v1/projects', ApiV1ProjectListController::class);
// 削除済みお知らせ通知の取消しID（認証不要・起動時掃除用）
Route::get('/v1/announcements/cancelled-ids', ApiV1AnnouncementCancelledIdsController::class);

Route::middleware(['web', 'bind.project'])->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);

    Route::post('/orders', [OrderController::class, 'store']);
    Route::post('/orders/{id}/cancel-unpaid', [OrderController::class, 'cancelUnpaid']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);

    Route::get('/members/me', [MemberMeController::class, '__invoke']);
    Route::put('/members/me', [MemberUpdateController::class, '__invoke']);
    Route::get('/postal-code', [PostalCodeController::class, '__invoke']);
    Route::get('/members/me/wallet-pass', [WalletPassController::class, '__invoke']);
    Route::get('/members/me/wallet-pass-url', [WalletPassUrlController::class, '__invoke']);
    Route::get('/members/me/google-wallet-url', [GoogleWalletUrlController::class, '__invoke']);
    Route::post('/members/register', [MemberRegisterController::class, '__invoke']);
    Route::post('/members/withdraw', [MemberWithdrawController::class, '__invoke']);
    Route::post('/devices/register', DeviceRegisterController::class);
    Route::get('/devices/session', DeviceSessionController::class);
    Route::get('/members/points', [MemberPointsController::class, '__invoke']);
    Route::get('/members/points/history', [MemberPointHistoryController::class, '__invoke']);
    Route::get('/members/points/card', [MemberPointCardController::class, '__invoke']);
    Route::post('/members/points/add', [MemberAddPointsController::class, '__invoke']);
    Route::get('/members/me/coupons', [CouponController::class, 'myCoupons']);
    Route::post('/members/me/coupons/{id}/use', [CouponController::class, 'use']);
    Route::get('/members/me/lottery', [MemberLotteryController::class, 'index']);
    Route::post('/members/me/lottery/{id}/play', [MemberLotteryController::class, 'play']);
    Route::post('/members/me/lottery/{id}/notify-win', [MemberLotteryController::class, 'notifyWin']);
    Route::get('/members/me/wallet-summary', MemberWalletSummaryController::class);

    Route::post('/payment/create-intent', [PaymentCreateIntentController::class, '__invoke']);
    Route::post('/payment/confirm', [PaymentConfirmController::class, '__invoke']);

    Route::get('/reservations', [ReservationIndexController::class, '__invoke']);
    Route::get('/reservations/available-dates', [ReservationAvailableDatesController::class, '__invoke']);
    Route::post('/reservations', [ReservationStoreController::class, '__invoke']);
    Route::put('/reservations/{id}', [ReservationUpdateController::class, '__invoke']);
    Route::post('/reservations/{id}/cancel', [ReservationCancelController::class, '__invoke']);

    Route::get('/announcements', [AnnouncementController::class, 'index']);
    Route::post('/announcements/{id}/read', [AnnouncementController::class, 'markRead']);
    Route::post('/contact', [ContactController::class, 'store']);
    Route::get('/gallery', [GalleryController::class, 'index']);
    Route::get('/stamps', [StampController::class, 'index']);

    Route::post('/queue', [QueueStoreController::class, '__invoke']);
    Route::get('/queue/me', [QueueMeController::class, '__invoke']);
    Route::get('/queue/list', [QueueListController::class, '__invoke']);
    Route::get('/queue/{id}', [QueueStatusController::class, '__invoke']);
    Route::post('/queue/{id}/cancel', [QueueCancelController::class, '__invoke']);
    Route::post('/queue/{id}/recall', [QueueRecallController::class, '__invoke']);
    Route::post('/queue/{id}/enter', [QueueEnterController::class, '__invoke']);
    Route::post('/queue/call-next', [QueueCallNextController::class, '__invoke']);
    Route::get('/queue/qr/{token}', [QueueQrStatusController::class, '__invoke']);
});

Route::post('/payment/webhook', [PaymentWebhookController::class, '__invoke']);
Route::match(['get', 'post'], '/client-debug', ClientDebugController::class);

// Apple Wallet PassKit Web Service
Route::post('/apple-wallet/v1/devices/{deviceLibraryIdentifier}/registrations/{passTypeIdentifier}/{serialNumber}', [AppleWalletPasskitController::class, 'registerDevice']);
Route::delete('/apple-wallet/v1/devices/{deviceLibraryIdentifier}/registrations/{passTypeIdentifier}/{serialNumber}', [AppleWalletPasskitController::class, 'unregisterDevice']);
Route::get('/apple-wallet/v1/devices/{deviceLibraryIdentifier}/registrations/{passTypeIdentifier}', [AppleWalletPasskitController::class, 'listUpdatedSerials']);
Route::get('/apple-wallet/v1/passes/{passTypeIdentifier}/{serialNumber}', [AppleWalletPasskitController::class, 'getLatestPass']);
