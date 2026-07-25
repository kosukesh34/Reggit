<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Requests\Admin\SettingsBasicRequest;
use App\UseCases\Admin\Settings\GetBasicSettingsUseCase;
use App\UseCases\Admin\Settings\RegenerateKitchenAccessTokenUseCase;
use App\UseCases\Admin\Settings\UpdateBasicFeatureFlagsUseCase;
use App\UseCases\Admin\Settings\UpdateBasicSettingsUseCase;
use Illuminate\Http\Request;

class BasicController extends AdminBaseController
{
    private GetBasicSettingsUseCase $getBasicSettingsUseCase;

    private UpdateBasicSettingsUseCase $updateBasicSettingsUseCase;

    private UpdateBasicFeatureFlagsUseCase $updateBasicFeatureFlagsUseCase;

    private RegenerateKitchenAccessTokenUseCase $regenerateKitchenAccessTokenUseCase;

    public function __construct(
        GetBasicSettingsUseCase $getBasicSettingsUseCase,
        UpdateBasicSettingsUseCase $updateBasicSettingsUseCase,
        UpdateBasicFeatureFlagsUseCase $updateBasicFeatureFlagsUseCase,
        RegenerateKitchenAccessTokenUseCase $regenerateKitchenAccessTokenUseCase
    ) {
        $this->getBasicSettingsUseCase = $getBasicSettingsUseCase;
        $this->updateBasicSettingsUseCase = $updateBasicSettingsUseCase;
        $this->updateBasicFeatureFlagsUseCase = $updateBasicFeatureFlagsUseCase;
        $this->regenerateKitchenAccessTokenUseCase = $regenerateKitchenAccessTokenUseCase;
    }

    public function index()
    {
        $projectId = $this->resolveAdminProjectId();
        $view = $this->getBasicSettingsUseCase->handle($projectId);

        return view('admin.settings.basic', [
            'settings' => $view->settings,
            'featureFlags' => $view->featureFlags,
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

    public function updateFeatureFlags(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $flags = $request->input('feature_flags', []);
        $this->updateBasicFeatureFlagsUseCase->handle($projectId, is_array($flags) ? $flags : []);

        return redirect()->route('admin.settings.basic', $this->buildAdminRouteParams())
            ->with('success', '利用機能を更新しました');
    }
}
