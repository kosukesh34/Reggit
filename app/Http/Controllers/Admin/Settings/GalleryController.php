<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\UseCases\Admin\Settings\GetGallerySettingsUseCase;
use App\UseCases\Admin\Settings\UpdateGallerySettingsUseCase;
use Illuminate\Http\Request;

class GalleryController extends AdminBaseController
{
    private GetGallerySettingsUseCase $getGallerySettingsUseCase;
    private UpdateGallerySettingsUseCase $updateGallerySettingsUseCase;

    public function __construct(
        GetGallerySettingsUseCase $getGallerySettingsUseCase,
        UpdateGallerySettingsUseCase $updateGallerySettingsUseCase
    ) {
        $this->getGallerySettingsUseCase = $getGallerySettingsUseCase;
        $this->updateGallerySettingsUseCase = $updateGallerySettingsUseCase;
    }

    public function index()
    {
        $projectId = $this->resolveAdminProjectId();
        $data = $this->getGallerySettingsUseCase->handle($projectId);

        return view('admin.settings.gallery', ['featureEnabled' => $data['featureEnabled']]);
    }

    public function update(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validate([
            'feature_gallery_enabled' => 'nullable|boolean',
        ]);
        $this->updateGallerySettingsUseCase->handle($projectId, $validated);

        return redirect()->route('admin.settings.gallery', $this->buildAdminRouteParams())
            ->with('success', 'ギャラリー設定を更新しました');
    }
}
