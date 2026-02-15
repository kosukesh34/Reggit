<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Requests\Admin\SettingsBasicRequest;
use App\Models\ShopSetting;
use App\Services\SettingsService;
use Illuminate\Http\Request;

class BasicController extends AdminBaseController
{
    public function __construct(
        private SettingsService $settingsService
    ) {
    }

    public function index()
    {
        $projectId = $this->resolveAdminProjectId();
        $settings = $this->settingsService->getBasicSettings($projectId);
        $featureFlags = ShopSetting::getFeatureFlags($projectId);
        return view('admin.settings.basic', compact('settings', 'featureFlags'));
    }

    public function update(SettingsBasicRequest $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $this->settingsService->updateReservationSettings($request->validated(), $projectId);
        return redirect()->route('admin.settings.basic', $this->buildAdminRouteParams())
            ->with('success', '基本設定を更新しました');
    }

    public function updateFeatureFlags(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        ShopSetting::setFeatureFlags($request->input('feature_flags', []), $projectId);
        return redirect()->route('admin.settings.basic', $this->buildAdminRouteParams())
            ->with('success', '利用機能を更新しました');
    }
}
