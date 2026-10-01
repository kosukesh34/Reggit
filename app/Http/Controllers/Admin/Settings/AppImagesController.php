<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Requests\Admin\SettingsAppImagesRequest;
use App\UseCases\Admin\Settings\GetAppImagesSettingsUseCase;
use App\UseCases\Admin\Settings\UpdateAppImagesUseCase;

class AppImagesController extends AdminBaseController
{
    public function index(GetAppImagesSettingsUseCase $getSettings)
    {
        return view('admin.settings.features', [
            'view' => $getSettings->handle($this->resolveAdminProjectId()),
            'featureSection' => 'branding',
            'featureRoute' => 'admin.settings.app-branding',
        ]);
    }

    public function update(SettingsAppImagesRequest $request, UpdateAppImagesUseCase $updateSettings)
    {
        try {
            $updateSettings->handle($request->toInput($this->resolveAdminProjectId()));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['home_images' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.settings.app-branding', $this->buildAdminRouteParams())
            ->with('success', '店舗名・画像を更新しました');
    }
}
