<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Requests\Admin\UpdateFeatureSettingsRequest;
use App\UseCases\Admin\Settings\GetFeatureSettingsUseCase;
use App\UseCases\Admin\Settings\UpdateFeatureSettingsUseCase;

class FeaturesController extends AdminBaseController
{
    public function index(GetFeatureSettingsUseCase $getSettings)
    {
        return view('admin.settings.features', [
            'view' => $getSettings->handle($this->resolveAdminProjectId()),
            'featureSection' => 'features',
            'featureRoute' => 'admin.settings.features',
        ]);
    }

    public function update(UpdateFeatureSettingsRequest $request, UpdateFeatureSettingsUseCase $updateSettings)
    {
        $updateSettings->handle($request->toInput($this->resolveAdminProjectId()));

        return redirect()->route('admin.settings.features', $this->buildAdminRouteParams())
            ->with('success', '利用機能を更新しました');
    }
}
