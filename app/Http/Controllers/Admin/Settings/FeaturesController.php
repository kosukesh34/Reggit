<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\UseCases\Admin\Settings\GetFeatureSettingsUseCase;
use App\UseCases\Admin\Settings\UpdateFeatureSettingsUseCase;
use Illuminate\Http\Request;

class FeaturesController extends AdminBaseController
{
    public function __construct(
        private GetFeatureSettingsUseCase $getFeatureSettingsUseCase,
        private UpdateFeatureSettingsUseCase $updateFeatureSettingsUseCase
    ) {
    }

    public function index()
    {
        $projectId = $this->resolveAdminProjectId();
        $view = $this->getFeatureSettingsUseCase->handle($projectId);

        return view('admin.settings.features', ['view' => $view]);
    }

    public function update(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $this->updateFeatureSettingsUseCase->handle($projectId, $request->all());

        return redirect()->route('admin.settings.features', $this->buildAdminRouteParams())
            ->with('success', '機能・カスタマイズ設定を更新しました');
    }
}
