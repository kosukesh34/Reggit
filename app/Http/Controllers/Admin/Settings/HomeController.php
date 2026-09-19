<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Requests\Admin\SettingsHomeRequest;
use App\Services\Admin\PublicImageStorageService;
use App\UseCases\Admin\AdminSavePublicImageUseCase;
use App\UseCases\Admin\Settings\GetHomeSettingsUseCase;
use App\UseCases\Admin\Settings\UpdateHomeSettingsUseCase;

class HomeController extends AdminBaseController
{
    private GetHomeSettingsUseCase $getHomeSettingsUseCase;

    private UpdateHomeSettingsUseCase $updateHomeSettingsUseCase;

    public function __construct(
        GetHomeSettingsUseCase $getHomeSettingsUseCase,
        UpdateHomeSettingsUseCase $updateHomeSettingsUseCase,
        private AdminSavePublicImageUseCase $saveImageUseCase,
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
        $validated = $request->validated();
        $raw = $validated['tabs'] ?? [];
        $navigationHeaderImageUrl = null;
        if ($request->hasFile('navigation_header_image')) {
            $navigationHeaderImageUrl = $this->saveImageUseCase->handle(
                $request->file('navigation_header_image'),
                $projectId,
                PublicImageStorageService::DIR_NAVIGATION_HEADER,
            );
        } elseif ($request->input('navigation_header_image_clear') === '1') {
            $navigationHeaderImageUrl = '';
        }
        $homeImageUrl = null;
        if ($request->hasFile('home_image')) {
            $homeImageUrl = $this->saveImageUseCase->handle(
                $request->file('home_image'),
                $projectId,
                PublicImageStorageService::DIR_HOME_IMAGE,
            );
        } elseif ($request->input('home_image_clear') === '1') {
            $homeImageUrl = '';
        }
        $this->updateHomeSettingsUseCase->handle(
            $projectId,
            is_array($raw) ? $raw : [],
            $navigationHeaderImageUrl,
            (string) ($validated['navigation_header_background_color'] ?? '#FFFFFF'),
            $homeImageUrl,
            (string) $validated['home_menu_layout'],
        );

        return redirect()->route('admin.settings.home', $this->buildAdminRouteParams())
            ->with('success', 'ホーム設定を更新しました');
    }
}
