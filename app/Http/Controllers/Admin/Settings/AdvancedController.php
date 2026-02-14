<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Requests\Admin\SettingsAdvancedRequest;
use App\Services\SettingsService;

class AdvancedController extends AdminBaseController
{
    public function __construct(
        private SettingsService $settingsService
    ) {
    }

    public function index()
    {
        $settings = $this->settingsService->getAdvancedSettings($this->resolveAdminProjectId());
        return view('admin.settings.advanced', ['settings' => $settings]);
    }

    public function update(SettingsAdvancedRequest $request)
    {
        $this->settingsService->updateLineThemeSettings($request->validated(), $this->resolveAdminProjectId());
        return redirect()->route('admin.settings.advanced', $this->buildAdminRouteParams())
            ->with('success', '配色設定を更新しました');
    }
}
