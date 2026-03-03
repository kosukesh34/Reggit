<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Requests\Admin\SettingsHomeRequest;
use App\UseCases\Admin\Settings\GetHomeSettingsUseCase;
use App\UseCases\Admin\Settings\UpdateHomeSettingsUseCase;

class HomeController extends AdminBaseController
{
    private GetHomeSettingsUseCase $getHomeSettingsUseCase;

    private UpdateHomeSettingsUseCase $updateHomeSettingsUseCase;

    public function __construct(
        GetHomeSettingsUseCase $getHomeSettingsUseCase,
        UpdateHomeSettingsUseCase $updateHomeSettingsUseCase
    ) {
        $this->getHomeSettingsUseCase = $getHomeSettingsUseCase;
        $this->updateHomeSettingsUseCase = $updateHomeSettingsUseCase;
    }

    public function index()
    {
        $projectId = $this->resolveAdminProjectId();
        $data = $this->getHomeSettingsUseCase->handle($projectId);

        return view('admin.settings.home', $data);
    }

    public function update(SettingsHomeRequest $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $raw = $request->validated()['tabs'] ?? [];
        $this->updateHomeSettingsUseCase->handle($projectId, is_array($raw) ? $raw : []);

        return redirect()->route('admin.settings.home', $this->buildAdminRouteParams())
            ->with('success', 'ホーム設定を更新しました');
    }
}
