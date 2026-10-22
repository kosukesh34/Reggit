<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Requests\Admin\SettingsBasicRequest;
use App\UseCases\Admin\Settings\GetBasicSettingsUseCase;
use App\UseCases\Admin\Settings\GetStoreAppCredentialsUseCase;
use App\UseCases\Admin\Settings\UpdateStoreOperationSettingsUseCase;

class BasicController extends AdminBaseController
{
    private GetBasicSettingsUseCase $getBasicSettingsUseCase;

    private UpdateStoreOperationSettingsUseCase $updateStoreOperationSettingsUseCase;

    public function __construct(
        GetBasicSettingsUseCase $getBasicSettingsUseCase,
        UpdateStoreOperationSettingsUseCase $updateStoreOperationSettingsUseCase
    ) {
        $this->getBasicSettingsUseCase = $getBasicSettingsUseCase;
        $this->updateStoreOperationSettingsUseCase = $updateStoreOperationSettingsUseCase;
    }

    public function index(GetStoreAppCredentialsUseCase $getCredentials)
    {
        $projectId = $this->resolveAdminProjectId();
        $view = $this->getBasicSettingsUseCase->handle($projectId);

        return view('admin.settings.basic', [
            'settings' => $view->settings,
            'storeAppCredentials' => $getCredentials->handle($projectId),
        ]);
    }

    public function update(SettingsBasicRequest $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $this->updateStoreOperationSettingsUseCase->handle($request->toInput($projectId));

        return redirect()->route('admin.settings.basic', $this->buildAdminRouteParams())
            ->with('success', '店舗運用設定を更新しました');
    }
}
