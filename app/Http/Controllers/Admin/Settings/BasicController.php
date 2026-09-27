<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Requests\Admin\SettingsBasicRequest;
use App\Models\StoreAppCredential;
use App\UseCases\Admin\Settings\GetBasicSettingsUseCase;
use App\UseCases\Admin\Settings\UpdateBasicSettingsUseCase;

class BasicController extends AdminBaseController
{
    private GetBasicSettingsUseCase $getBasicSettingsUseCase;

    private UpdateBasicSettingsUseCase $updateBasicSettingsUseCase;

    public function __construct(
        GetBasicSettingsUseCase $getBasicSettingsUseCase,
        UpdateBasicSettingsUseCase $updateBasicSettingsUseCase
    ) {
        $this->getBasicSettingsUseCase = $getBasicSettingsUseCase;
        $this->updateBasicSettingsUseCase = $updateBasicSettingsUseCase;
    }

    public function index()
    {
        $projectId = $this->resolveAdminProjectId();
        $view = $this->getBasicSettingsUseCase->handle($projectId);

        return view('admin.settings.basic', [
            'settings' => $view->settings,
            'storeAppCredentials' => StoreAppCredential::query()
                ->where('project_id', $projectId)->get()->keyBy('app'),
        ]);
    }

    public function update(SettingsBasicRequest $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $this->updateBasicSettingsUseCase->handle($projectId, $request->validated());

        return redirect()->route('admin.settings.basic', $this->buildAdminRouteParams())
            ->with('success', '基本設定を更新しました');
    }
}
