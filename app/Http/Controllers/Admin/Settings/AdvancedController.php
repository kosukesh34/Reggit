<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Requests\Admin\SettingsAdvancedRequest;
use App\UseCases\Admin\Settings\GetAdvancedSettingsUseCase;
use App\UseCases\Admin\Settings\UpdateAdvancedSettingsUseCase;

class AdvancedController extends AdminBaseController
{
    private GetAdvancedSettingsUseCase $getAdvancedSettingsUseCase;
    private UpdateAdvancedSettingsUseCase $updateAdvancedSettingsUseCase;

    public function __construct(
        GetAdvancedSettingsUseCase $getAdvancedSettingsUseCase,
        UpdateAdvancedSettingsUseCase $updateAdvancedSettingsUseCase
    ) {
        $this->getAdvancedSettingsUseCase = $getAdvancedSettingsUseCase;
        $this->updateAdvancedSettingsUseCase = $updateAdvancedSettingsUseCase;
    }

    public function index()
    {
        $projectId = $this->resolveAdminProjectId();
        $view = $this->getAdvancedSettingsUseCase->handle($projectId);

        return view('admin.settings.advanced', ['settings' => $view->settings]);
    }

    public function update(SettingsAdvancedRequest $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $this->updateAdvancedSettingsUseCase->handle($projectId, $request->validated());

        return redirect()->route('admin.settings.advanced', $this->buildAdminRouteParams())
            ->with('success', '配色設定を更新しました');
    }
}
