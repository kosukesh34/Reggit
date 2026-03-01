<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Requests\Admin\SettingsMenuRequest;
use App\UseCases\Admin\Settings\GetMenuSettingsUseCase;
use App\UseCases\Admin\Settings\UpdateMenuSettingsUseCase;

class MenuController extends AdminBaseController
{
    private GetMenuSettingsUseCase $getMenuSettingsUseCase;

    private UpdateMenuSettingsUseCase $updateMenuSettingsUseCase;

    public function __construct(
        GetMenuSettingsUseCase $getMenuSettingsUseCase,
        UpdateMenuSettingsUseCase $updateMenuSettingsUseCase
    ) {
        $this->getMenuSettingsUseCase = $getMenuSettingsUseCase;
        $this->updateMenuSettingsUseCase = $updateMenuSettingsUseCase;
    }

    public function index()
    {
        $projectId = $this->resolveAdminProjectId();
        $settings = $this->getMenuSettingsUseCase->handle($projectId);

        return view('admin.settings.menu', ['settings' => $settings]);
    }

    public function update(SettingsMenuRequest $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $this->updateMenuSettingsUseCase->handle($projectId, $request->validated());

        return redirect()->route('admin.settings.menu', $this->buildAdminRouteParams())
            ->with('success', 'メニュー設定を更新しました');
    }
}
