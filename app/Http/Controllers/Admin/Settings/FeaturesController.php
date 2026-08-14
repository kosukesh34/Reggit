<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Requests\Admin\UpdateFeatureSettingsRequest;
use App\UseCases\Admin\Settings\GetFeatureSettingsUseCase;
use App\UseCases\Admin\Settings\UpdateFeatureSettingsUseCase;
use Illuminate\Http\UploadedFile;

class FeaturesController extends AdminBaseController
{
    public function __construct(
        private GetFeatureSettingsUseCase $getFeatureSettingsUseCase,
        private UpdateFeatureSettingsUseCase $updateFeatureSettingsUseCase
    ) {}

    public function index()
    {
        $projectId = $this->resolveAdminProjectId();
        $view = $this->getFeatureSettingsUseCase->handle($projectId);

        return view('admin.settings.features', ['view' => $view]);
    }

    public function update(UpdateFeatureSettingsRequest $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $frontendLogo = $request->file('frontend_logo');
        $stampDefaultImage = $request->file('stamp_default_image');
        $this->updateFeatureSettingsUseCase->handle(
            $projectId,
            $request->all(),
            $frontendLogo instanceof UploadedFile ? $frontendLogo : null,
            $request->input('frontend_logo_clear') === '1',
            $stampDefaultImage instanceof UploadedFile ? $stampDefaultImage : null,
            $request->input('stamp_default_image_clear') === '1',
        );

        return redirect()->route('admin.settings.features', $this->buildAdminRouteParams())
            ->with('success', '機能・カスタマイズ設定を更新しました');
    }
}
