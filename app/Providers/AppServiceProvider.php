<?php

namespace App\Providers;

use App\Models\Coupon;
use App\Models\MemberStamp;
use App\Models\Product;
use App\Models\Project;
use App\Models\ShopSetting;
use App\Observers\MemberStampObserver;
use App\Observers\ProjectObserver;
use Database\Seeders\DefaultDataSeeder;
use App\UseCases\Payment\PaymentGateway;
use App\UseCases\Payment\StripePaymentGateway;
use App\Util\AdminContext;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGateway::class, function () {
            $secret = (string) config('services.stripe.secret', env('STRIPE_SECRET', ''));

            return new StripePaymentGateway($secret);
        });

        $this->app->singleton(Client::class, function () {
            return new Client(['timeout' => 15]);
        });
    }

    public function boot(): void
    {
        Project::observe(ProjectObserver::class);
        MemberStamp::observe(MemberStampObserver::class);

        if ($this->app->runningInConsole() === false) {
            if (request()->isSecure() ||
                request()->header('X-Forwarded-Proto') === 'https' ||
                request()->header('X-Forwarded-Ssl') === 'on') {
                URL::forceScheme('https');
            }
        }

        // CLI（composer, package:discover 等）では DB 未接続のためスキップ
        if ($this->app->runningInConsole() === false) {
            try {
                if (Schema::hasTable('projects') && Project::count() === 0) {
                    Project::create([
                        'project_key' => 'default',
                        'name' => 'デフォルト店舗',
                        'description' => '複数店舗対応前の既存データ用',
                        'is_active' => true,
                    ]);
                }
            } catch (\Throwable $e) {
                // マイグレーション前や DB 未起動時は無視
            }
        }

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
                $view->with('adminSidebarColor', ShopSetting::getAdminSidebarColor($currentProjectId));
                $view->with('adminHeaderColor', ShopSetting::getAdminHeaderColor($currentProjectId));
                $view->with('adminPrimaryColor', ShopSetting::getAdminPrimaryColor($currentProjectId));
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

                // ローカル開発時: 初期データが空なら最低限のサンプルデータを自動投入する。
                // setup時に seed を省略しても localhost でデフォルト画面を確認できるようにする。
                if (app()->environment(['local', 'development']) && $project !== null) {
                    $hasProducts = Product::withoutGlobalScopes()->where('project_id', $projectId)->exists();
                    $hasCoupons = Coupon::where('project_id', $projectId)->exists();
                    if (! $hasProducts || ! $hasCoupons) {
                        try {
                            app(DefaultDataSeeder::class)->runForProject($project, false, false);
                        } catch (\Throwable $e) {
                            // 自動seed失敗時は画面表示を優先
                        }
                    }
                }

                $view->with('lineTheme', ShopSetting::getLineThemeColors($projectId));
                $view->with('frontendMenu', ShopSetting::getFrontendMenuItems($projectId));
                $view->with('projectKey', $project ? ((string) $project->project_key) : 'default');
                $view->with('projectName', $project ? ((string) $project->name) : 'モバイルオーダー');
                $view->with('featureFlags', ShopSetting::getFeatureFlags($projectId));
                $view->with('walletPassEnabled', config('wallet_pass.enabled', false));
                $view->with('googleWalletEnabled', config('google_wallet.enabled', false));
                $view->with('frontendLogoUrl', ShopSetting::getFrontendLogoUrl($projectId));
                $view->with('frontendHeaderText', ShopSetting::getFrontendHeaderText($projectId));
                $view->with('orderMode', ShopSetting::getOrderMode($projectId));
                $view->with('liffBuilderBlocks', ShopSetting::getLiffBuilderBlocks($projectId));
                // ユーザー画面は常に標準デザインを使用する（管理画面でのカスタム保存値は反映しない）
                $view->with('liffTabLayouts', ShopSetting::defaultLiffTabLayouts());
                $view->with('liffCustomCss', '');
                $view->with('liffTabSettings', ShopSetting::defaultLiffTabSettings());
                $view->with('liffId', ShopSetting::getLineLiffId($projectId));
                $view->with('liffUrl', ShopSetting::getLineLiffUrl($projectId));
                $view->with('lineFriendAddUrl', ShopSetting::getLineFriendAddUrl($projectId));

                // LIFFデザイナー埋め込みプレビュー（会員証）: ランク設定から実際の表示用ペイロードを組み立てる
                $designerPreviewMemberPayload = null;
                if ($req && $req->query('designer_live_preview') === '1' && $req->query('designer_embed') === '1') {
                    $src = $req->query('designer_source');
                    if ($src === 'member' || $src === 'member_card') {
                        $designerPreviewMemberPayload = self::buildDesignerPreviewMemberPayload($projectId);
                    }
                }
                $view->with('designerPreviewMemberPayload', $designerPreviewMemberPayload);
            } catch (\Throwable $e) {
                $view->with('lineTheme', [
                    'primary' => '#000000',
                    'primary_rgb' => '0, 0, 0',
                    'primary_dark' => '#333333',
                    'success' => '#000000',
                    'danger' => '#dc3545',
                ]);
                $view->with('frontendMenu', ShopSetting::defaultFrontendMenuItems());
                $view->with('projectKey', 'default');
                $view->with('projectName', 'モバイルオーダー');
                $view->with('featureFlags', []);
                $view->with('walletPassEnabled', false);
                $view->with('googleWalletEnabled', false);
                $view->with('frontendLogoUrl', '');
                $view->with('frontendHeaderText', 'モバイルオーダー');
                $view->with('orderMode', ShopSetting::ORDER_MODE_BOTH);
                $view->with('liffBuilderBlocks', ShopSetting::defaultLiffBuilderTemplates()[0]['blocks']);
                $view->with('liffTabLayouts', ShopSetting::defaultLiffTabLayouts());
                $view->with('liffCustomCss', '');
                $view->with('liffTabSettings', ShopSetting::defaultLiffTabSettings());
                $view->with('liffId', trim((string) config('line.liff_id', '')));
                $view->with('liffUrl', trim((string) config('line.liff_url', '')));
                $view->with('lineFriendAddUrl', '');
                $view->with('designerPreviewMemberPayload', null);
            }
        });
    }

    /**
     * LIFFデザイナー埋め込みプレビュー用に、DBのランク設定・会員証設定から会員証表示用のサンプルペイロードを組み立てる。
     * 実際の画面で表示される会員証UIと同じ設定（レイアウト・ランク色・バーコード/QR等）でプレビューできるようにする。
     */
    private static function buildDesignerPreviewMemberPayload(int $projectId): array
    {
        $ranks = ShopSetting::getMemberRankSettings($projectId);
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
            'days_to_next_rank' => 18,
            'days_required_for_next_rank' => 30,
            'show_points_display' => ShopSetting::getShowPointsDisplay($projectId),
            'show_rank_progress' => ShopSetting::getShowRankProgress($projectId),
            'member_card_design' => ShopSetting::getMemberCardDesign($projectId),
            'member_card_logo_url' => ShopSetting::getFrontendLogoUrl($projectId),
            'member_card_display_type' => ShopSetting::getMemberCardDisplayType($projectId),
            'member_card_code_type' => ShopSetting::getMemberCardCodeType($projectId),
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
