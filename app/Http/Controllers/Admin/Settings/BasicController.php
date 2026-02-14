<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Requests\Admin\SettingsBasicRequest;
use App\Services\SettingsService;

class BasicController extends AdminBaseController
{
    public function __construct(
        private SettingsService $settingsService
    ) {
    }

    public function index()
    {
        $settings = $this->settingsService->getBasicSettings($this->resolveAdminProjectId());
        return view('admin.settings.basic', ['settings' => $settings]);
    }

    public function update(SettingsBasicRequest $request)
    {
        $this->settingsService->updateReservationSettings($request->validated(), $this->resolveAdminProjectId());
        return redirect()->route('admin.settings.basic', $this->buildAdminRouteParams())
            ->with('success', '基本設定を更新しました');
    }
}
