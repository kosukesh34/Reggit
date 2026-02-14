<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Requests\Admin\SettingsMenuRequest;
use App\Services\SettingsService;

class MenuController extends AdminBaseController
{
    public function __construct(
        private SettingsService $settingsService
    ) {
    }

    public function index()
    {
        $settings = $this->settingsService->getMenuSettings($this->resolveAdminProjectId());
        return view('admin.settings.menu', ['settings' => $settings]);
    }

    public function update(SettingsMenuRequest $request)
    {
        $this->settingsService->updateMenuSettings($request->validated(), $this->resolveAdminProjectId());
        return redirect()->route('admin.settings.menu', $this->buildAdminRouteParams())
            ->with('success', 'メニュー設定を更新しました');
    }
}
