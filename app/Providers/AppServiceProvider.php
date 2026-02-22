<?php

namespace App\Providers;

use App\Models\MemberStamp;
use App\Models\Project;
use App\Models\ShopSetting;
use App\Observers\MemberStampObserver;
use App\Observers\ProjectObserver;
use App\Util\AdminContext;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
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

        // View composer は Web リクエスト時のみ登録（CLI で package:discover 等実行時に DB 接続を避ける）
        if ($this->app->runningInConsole() === false) {
            View::composer('index', function ($view) {
                try {
                    // LIFF は bind.project で request にプロジェクトがセットされる。それを優先する
                    $project = request()->attributes->get('currentProject') ?? AdminContext::getCurrentProject();
                    $projectId = $project?->id ?? 1;
                    $view->with('lineTheme', ShopSetting::getLineThemeColors($projectId));
                    $view->with('frontendMenu', ShopSetting::getFrontendMenuItems($projectId));
                    $view->with('projectKey', $project?->project_key ?? 'default');
                    $view->with('projectName', $project?->name ?? 'モバイルオーダー');
                    $view->with('featureFlags', ShopSetting::getFeatureFlags($projectId));
                    $view->with('walletPassEnabled', config('wallet_pass.enabled', false));
                    $view->with('frontendLogoUrl', ShopSetting::getFrontendLogoUrl($projectId));
                    $view->with('frontendHeaderText', ShopSetting::getFrontendHeaderText($projectId));
                    $view->with('liffBuilderBlocks', ShopSetting::getLiffBuilderBlocks($projectId));
                    // LIFF レイアウトは LiffController で request のプロジェクトを元に渡すため、composer では上書きしない（/ ルート用のみセット）
                    if (request()->attributes->get('currentProject') === null) {
                        $view->with('liffTabLayouts', ShopSetting::getLiffTabLayouts($projectId));
                        $view->with('liffCustomCss', ShopSetting::getLiffCustomCss($projectId));
                        $view->with('liffTabSettings', ShopSetting::getLiffTabSettings($projectId));
                    }
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
                    $view->with('walletPassEnabled', false);
                    $view->with('liffBuilderBlocks', ShopSetting::defaultLiffBuilderTemplates()[0]['blocks']);
                    $view->with('liffTabLayouts', ShopSetting::defaultLiffTabLayouts());
                    $view->with('liffCustomCss', '');
                    $view->with('liffTabSettings', []);
                }
            });

            View::composer('admin.layout', function ($view) {
                if (!auth('admin')->check()) {
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
        }
    }
}
