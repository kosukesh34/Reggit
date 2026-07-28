<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Requests\Admin\SettingsBasicRequest;
use App\UseCases\Admin\Settings\GetBasicSettingsUseCase;
use App\UseCases\Admin\Settings\RegenerateKitchenAccessTokenUseCase;
use App\UseCases\Admin\Settings\UpdateBasicSettingsUseCase;

class BasicController extends AdminBaseController
{
    private GetBasicSettingsUseCase $getBasicSettingsUseCase;

    private UpdateBasicSettingsUseCase $updateBasicSettingsUseCase;

    private RegenerateKitchenAccessTokenUseCase $regenerateKitchenAccessTokenUseCase;

    public function __construct(
        GetBasicSettingsUseCase $getBasicSettingsUseCase,
        UpdateBasicSettingsUseCase $updateBasicSettingsUseCase,
        RegenerateKitchenAccessTokenUseCase $regenerateKitchenAccessTokenUseCase
    ) {
        $this->getBasicSettingsUseCase = $getBasicSettingsUseCase;
        $this->updateBasicSettingsUseCase = $updateBasicSettingsUseCase;
        $this->regenerateKitchenAccessTokenUseCase = $regenerateKitchenAccessTokenUseCase;
    }

    public function index()
    {
        $projectId = $this->resolveAdminProjectId();
        $view = $this->getBasicSettingsUseCase->handle($projectId);

        return view('admin.settings.basic', [
            'settings' => $view->settings,
        ]);
    }

    public function update(SettingsBasicRequest $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $this->updateBasicSettingsUseCase->handle($projectId, $request->validated());

        return redirect()->route('admin.settings.basic', $this->buildAdminRouteParams())
            ->with('success', '基本設定を更新しました');
    }

    public function regenerateKitchenToken()
    {
        $projectId = $this->resolveAdminProjectId();
        $this->regenerateKitchenAccessTokenUseCase->handle($projectId);

        return redirect()->route('admin.settings.basic', $this->buildAdminRouteParams())
            ->with('success', 'キッチンモニター用トークンを発行しました');
    }
}
