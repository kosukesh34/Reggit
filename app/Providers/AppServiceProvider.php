<?php

namespace App\Providers;

use App\Domain\Coupon\Repositories\CouponDeliveryTargetRepository;
use App\Domain\Coupon\Repositories\MemberCouponRepository;
use App\Domain\Member\Repositories\MemberRepository;
use App\Domain\Order\Repositories\OrderRepository;
use App\Domain\Order\Repositories\ProductCatalogRepository;
use App\Domain\Order\Repositories\ProductStockRepository;
use App\Domain\Queue\Repositories\QueueEntryRepository;
use App\Domain\Reservation\Repositories\ReservationRepository;
use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Domain\Settings\ShopSettingKeys;
use App\Domain\Shared\DomainEventDispatcher\DomainEventDispatcher;
use App\Enums\MemberRegistrationMode;
use App\Infrastructure\Events\LoggingDomainEventListener;
use App\Infrastructure\Events\MulticastDomainEventDispatcher;
use App\Infrastructure\Events\NotifyOnOrderConfirmedListener;
use App\Infrastructure\Events\NotifyOnOrderReadyListener;
use App\Infrastructure\Events\SyncOrderPointsToSmaregiListener;
use App\Infrastructure\Events\SyncOrderTransactionToSmaregiListener;
use App\Infrastructure\Events\SyncPointsEarnedToSmaregiListener;
use App\Infrastructure\Persistence\Eloquent\Coupon\EloquentCouponDeliveryTargetRepository;
use App\Infrastructure\Persistence\Eloquent\Coupon\EloquentMemberCouponRepository;
use App\Infrastructure\Persistence\Eloquent\Member\EloquentMemberRepository;
use App\Infrastructure\Persistence\Eloquent\Order\EloquentOrderRepository;
use App\Infrastructure\Persistence\Eloquent\Order\EloquentProductCatalogRepository;
use App\Infrastructure\Persistence\Eloquent\Order\EloquentProductStockRepository;
use App\Infrastructure\Persistence\Eloquent\Queue\EloquentQueueEntryRepository;
use App\Infrastructure\Persistence\Eloquent\Reservation\EloquentReservationRepository;
use App\Infrastructure\Persistence\Eloquent\Settings\EloquentShopSettingRepository;
use App\Models\MemberCoupon;
use App\Models\MemberLotteryEntitlement;
use App\Models\MemberStamp;
use App\Models\PointTransaction;
use App\Models\Project;
use App\Observers\MemberCouponObserver;
use App\Observers\MemberLotteryEntitlementObserver;
use App\Observers\MemberStampObserver;
use App\Observers\PointTransactionObserver;
use App\Observers\ProjectObserver;
use App\Repositories\Contracts\AdminUserRepository;
use App\Repositories\Contracts\ProjectRepository;
use App\Repositories\Contracts\UserDeviceRepository;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\Eloquent\EloquentAdminUserRepository;
use App\Repositories\Eloquent\EloquentProjectRepository;
use App\Repositories\Eloquent\EloquentUserDeviceRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use App\Services\GoogleWalletPassService;
use App\Services\Smaregi\SmaregiApiClient;
use App\Services\WalletPassService;
use App\UseCases\Payment\PaymentGateway;
use App\UseCases\Payment\StripePaymentGateway;
use App\Util\AdminContext;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProjectRepository::class, EloquentProjectRepository::class);
        $this->app->bind(AdminUserRepository::class, EloquentAdminUserRepository::class);
        $this->app->bind(UserDeviceRepository::class, EloquentUserDeviceRepository::class);
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
        $this->app->bind(MemberRepository::class, EloquentMemberRepository::class);
        $this->app->bind(OrderRepository::class, EloquentOrderRepository::class);
        $this->app->bind(ProductStockRepository::class, EloquentProductStockRepository::class);
        $this->app->bind(ProductCatalogRepository::class, EloquentProductCatalogRepository::class);
        $this->app->bind(MemberCouponRepository::class, EloquentMemberCouponRepository::class);
        $this->app->bind(CouponDeliveryTargetRepository::class, EloquentCouponDeliveryTargetRepository::class);
        $this->app->bind(ReservationRepository::class, EloquentReservationRepository::class);
        $this->app->bind(QueueEntryRepository::class, EloquentQueueEntryRepository::class);
        $this->app->bind(ShopSettingRepository::class, EloquentShopSettingRepository::class);
        $this->app->singleton(DomainEventDispatcher::class, function ($app) {
            return new MulticastDomainEventDispatcher([
                $app->make(LoggingDomainEventListener::class),
                $app->make(NotifyOnOrderConfirmedListener::class),
                $app->make(NotifyOnOrderReadyListener::class),
                $app->make(SyncPointsEarnedToSmaregiListener::class),
                $app->make(SyncOrderPointsToSmaregiListener::class),
                $app->make(SyncOrderTransactionToSmaregiListener::class),
            ]);
        });

        $this->app->singleton(PaymentGateway::class, function () {
            $secret = (string) config('services.stripe.secret', '');

            return new StripePaymentGateway($secret);
        });

        $this->app->singleton(Client::class, function () {
            return new Client(['timeout' => 15]);
        });

        // Service と Resolver で同一インスタンスを共有しつつ、リクエスト／ジョブ単位で破棄する
        $this->app->scoped(SmaregiApiClient::class);
    }

    public function boot(): void
    {
        Project::observe(ProjectObserver::class);
        MemberStamp::observe(MemberStampObserver::class);
        MemberCoupon::observe(MemberCouponObserver::class);
        PointTransaction::observe(PointTransactionObserver::class);
        MemberLotteryEntitlement::observe(MemberLotteryEntitlementObserver::class);
        require base_path('routes/channels.php');

        if ($this->app->runningInConsole() === false) {
            if (request()->isSecure() ||
                request()->header('X-Forwarded-Proto') === 'https' ||
                request()->header('X-Forwarded-Ssl') === 'on') {
                URL::forceScheme('https');
            }
        }

        // CLI（composer, package:discover 等）では DB 未接続のためスキップ
        // 空 DB 時の default プロジェクト自動作成は行わない（オンボーディングで作成する）

        // admin.layout はテスト時にも必要（認証済み管理画面で $projects 等を渡す）
        View::composer('admin.layout', function ($view) {
            if (! auth('admin')->check()) {
                return;
            }
            $adminUser = auth('admin')->user();
            $isSystemAdmin = $adminUser->isSystemAdmin();
            $allowedIds = $adminUser->getAllowedProjectIds();
            $projects = $isSystemAdmin
                ? Project::where('is_active', true)->orderBy('name')->get()
                : Project::whereIn('id', $allowedIds)->where('is_active', true)->orderBy('name')->get();
            $sessionProjectId = (int) session('admin_project_id');
            if ($sessionProjectId && $adminUser->canAccessProject($sessionProjectId)) {
                $currentProjectId = $sessionProjectId;
            } elseif ($projects->isNotEmpty()) {
                $currentProjectId = (int) $projects->first()->id;
            } else {
                $currentProjectId = $adminUser->project_id ? (int) $adminUser->project_id : 1;
            }
            $view->with(compact('adminUser', 'currentProjectId', 'projects', 'isSystemAdmin'));

            try {
                $settingsRepo = app(ShopSettingRepository::class);
                $view->with('adminSidebarColor', $settingsRepo->getAdminSidebarColor($currentProjectId));
                $view->with('adminHeaderColor', $settingsRepo->getAdminHeaderColor($currentProjectId));
                $view->with('adminPrimaryColor', $settingsRepo->getAdminPrimaryColor($currentProjectId));
            } catch (\Throwable $e) {
                $view->with('adminSidebarColor', null);
                $view->with('adminHeaderColor', null);
                $view->with('adminPrimaryColor', null);
            }
        });

        // フロントエンドの View composer（LINE / ネイティブ両方に共通の変数を渡す）
        View::composer(['index', 'frontend.line', 'frontend.native'], function ($view) {
            try {
                $req = request();
                $project = null;
                if ($req && $req->attributes) {
                    $project = $req->attributes->get('currentProject');
                }
                if ($project === null) {
                    $project = AdminContext::getCurrentProject();
                }
                $projectId = $project ? ((int) $project->id) : 1;
                $settingsRepo = app(ShopSettingRepository::class);
                $view->with('lineTheme', $settingsRepo->getLineThemeColors($projectId));
                $view->with('frontendMenu', $settingsRepo->getFrontendMenuItems($projectId));
                $view->with('projectKey', $project ? ((string) $project->project_key) : '');
                $view->with('projectName', $project ? ((string) $project->name) : config('app.name'));
                $view->with('featureFlags', $settingsRepo->getFeatureFlags($projectId));
                $googleWalletPassService = app(GoogleWalletPassService::class);
                $walletPassService = app(WalletPassService::class);
                $view->with('walletPassEnabled', $walletPassService->isAvailable());
                $view->with('googleWalletEnabled', $googleWalletPassService->isAvailable());
                $view->with('frontendLogoUrl', $settingsRepo->getFrontendLogoUrl($projectId));
                $view->with('frontendHeaderText', $settingsRepo->getFrontendHeaderText($projectId));
                $view->with('orderMode', $settingsRepo->getOrderMode($projectId));
                $view->with('liffTabLayouts', $settingsRepo->getLiffTabLayouts($projectId));
                $view->with('liffDetailLayouts', $settingsRepo->getLiffDetailLayouts($projectId));
                $view->with('liffCustomCss', $settingsRepo->getLiffCustomCss($projectId));
                $view->with('liffTabSettings', $settingsRepo->getLiffTabSettings($projectId));
                $view->with('liffId', $settingsRepo->getLineLiffId($projectId));
                $view->with('liffUrl', $settingsRepo->getLineLiffUrl($projectId));
                $view->with('lineFriendAddUrl', $settingsRepo->getLineFriendAddUrl($projectId));
                $view->with('queueRequireMembership', $settingsRepo->getQueueRequireMembership($projectId));
                $view->with('memberRegistrationMode', $settingsRepo->getMemberRegistrationMode($projectId));

                // LIFFデザイナー埋め込みプレビュー（会員証）: ランク設定から実際の表示用ペイロードを組み立てる
                $designerPreviewMemberPayload = null;
                if ($req && $req->query('designer_live_preview') === '1' && $req->query('designer_embed') === '1') {
                    $src = $req->query('designer_source');
                    if ($src === 'member' || $src === 'member_card') {
                        $designerPreviewMemberPayload = self::buildDesignerPreviewMemberPayloadForProject($projectId);
                    }
                }
                $view->with('designerPreviewMemberPayload', $designerPreviewMemberPayload);
            } catch (\Throwable $e) {
                $view->with('lineTheme', [
                    'primary' => '#4f46e5',
                    'primary_rgb' => '79, 70, 229',
                    'primary_dark' => '#4338ca',
                    'success' => '#059669',
                    'danger' => '#dc2626',
                ]);
                $fallbackRepo = app(ShopSettingRepository::class);
                $view->with('frontendMenu', $fallbackRepo->defaultFrontendMenuItems());
                $view->with('projectKey', '');
                $view->with('projectName', config('app.name'));
                $view->with('featureFlags', []);
                $view->with('walletPassEnabled', false);
                $view->with('googleWalletEnabled', false);
                $view->with('frontendLogoUrl', '');
                $view->with('frontendHeaderText', config('app.name'));
                $view->with('orderMode', ShopSettingKeys::ORDER_MODE_BOTH);
                $view->with('liffTabLayouts', $fallbackRepo->defaultLiffTabLayouts());
                $view->with('liffDetailLayouts', $fallbackRepo->defaultLiffDetailLayouts());
                $view->with('liffCustomCss', '');
                $view->with('liffTabSettings', []);
                $view->with('liffId', trim((string) config('line.liff_id', '')));
                $view->with('liffUrl', trim((string) config('line.liff_url', '')));
                $view->with('lineFriendAddUrl', '');
                $view->with('queueRequireMembership', false);
                $view->with('memberRegistrationMode', MemberRegistrationMode::OPTIONAL);
                $view->with('designerPreviewMemberPayload', null);
            }
        });
    }

    /**
     * LIFFデザイナー埋め込みプレビュー用に、DBのランク設定・会員証設定から会員証表示用のサンプルペイロードを組み立てる。
     * 実際の画面で表示される会員証UIと同じ設定（レイアウト・ランク色・バーコード/QR等）でプレビューできるようにする。
     */
    public static function buildDesignerPreviewMemberPayloadForProject(int $projectId): array
    {
        $settingsRepo = app(ShopSettingRepository::class);
        $ranks = $settingsRepo->getMemberRankSettings($projectId);
        $activeRanks = array_values(array_filter($ranks, fn ($r) => ($r['is_active'] ?? false) === true));
        usort($activeRanks, fn ($a, $b) => ($a['min_points'] <=> $b['min_points']) ?: (($a['order'] ?? 0) <=> ($b['order'] ?? 0)));

        $displayRank = $activeRanks[0] ?? [
            'key' => 'bronze',
            'label' => 'ブロンズ',
            'min_points' => 0,
            'icon_url' => null,
            'card_color' => '#cd7f32',
        ];
        $nextRank = null;
        foreach ($activeRanks as $r) {
            if (($r['min_points'] ?? 0) > ($displayRank['min_points'] ?? 0)) {
                $nextRank = $r;
                break;
            }
        }

        $minPoints = (int) ($displayRank['min_points'] ?? 0);
        $samplePoints = $minPoints + 500;
        $pointsToNext = $nextRank !== null ? max(0, ((int) ($nextRank['min_points'] ?? 0)) - $samplePoints) : null;

        return [
            'is_member' => true,
            'member' => [
                'member_number' => '000123456789',
                'points' => $samplePoints,
            ],
            'user' => [
                'name' => 'プレビュー会員',
            ],
            'points' => $samplePoints,
            'current_rank' => $displayRank['key'] ?? 'bronze',
            'current_rank_label' => $displayRank['label'] ?? 'ブロンズ',
            'current_rank_icon' => $displayRank['icon_url'] ?? null,
            'current_rank_color' => isset($displayRank['card_color']) && $displayRank['card_color'] !== '' ? (string) $displayRank['card_color'] : null,
            'next_rank_label' => $nextRank !== null ? ($nextRank['label'] ?? null) : null,
            'points_to_next_rank' => $pointsToNext,
            'days_to_next_rank' => null,
            'days_required_for_next_rank' => $settingsRepo->getRankAggregationType($projectId) === 'period'
                ? $settingsRepo->getRankPeriodDays($projectId)
                : null,
            'rank_basis' => $settingsRepo->getRankBasis($projectId),
            'rank_aggregation_type' => $settingsRepo->getRankAggregationType($projectId),
            'show_points_display' => $settingsRepo->getShowPointsDisplay($projectId)
                && $settingsRepo->getUsePoints($projectId),
            'show_rank_progress' => $settingsRepo->getShowRankProgress($projectId),
            'member_card_design' => $settingsRepo->getMemberCardDesign($projectId),
            'member_card_logo_url' => $settingsRepo->getFrontendLogoUrl($projectId),
            'member_card_display_type' => $settingsRepo->getMemberCardDisplayType($projectId),
            'member_card_code_type' => $settingsRepo->getMemberCardCodeType($projectId),
            'points_expiry' => [
                'expires_at_label' => '2026/12/31',
                'points_expiring' => 200,
            ],
            'rank_up_history' => [
                ['from_rank_label' => 'ブロンズ', 'to_rank_label' => 'シルバー', 'achieved_at' => '2025/11/03'],
                ['from_rank_label' => 'シルバー', 'to_rank_label' => $displayRank['label'] ?? 'ゴールド', 'achieved_at' => '2026/01/20'],
            ],
        ];
    }
}
