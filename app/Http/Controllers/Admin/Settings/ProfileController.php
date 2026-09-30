<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Requests\Admin\SettingsProfileRequest;
use App\Http\Support\AdminSettingsInputFactory;
use App\UseCases\Admin\Settings\GetMemberCodeSettingsUseCase;
use App\UseCases\Admin\Settings\GetProfileSettingsUseCase;
use App\UseCases\Admin\Settings\UpdateProfileSettingsUseCase;

class ProfileController extends AdminBaseController
{
    private GetProfileSettingsUseCase $getProfileSettingsUseCase;

    private UpdateProfileSettingsUseCase $updateProfileSettingsUseCase;

    private AdminSettingsInputFactory $inputFactory;

    public function __construct(
        GetProfileSettingsUseCase $getProfileSettingsUseCase,
        UpdateProfileSettingsUseCase $updateProfileSettingsUseCase,
        AdminSettingsInputFactory $inputFactory
    ) {
        $this->getProfileSettingsUseCase = $getProfileSettingsUseCase;
        $this->updateProfileSettingsUseCase = $updateProfileSettingsUseCase;
        $this->inputFactory = $inputFactory;
    }

    public function index(GetMemberCodeSettingsUseCase $getMemberCode)
    {
        $projectId = $this->resolveAdminProjectId();
        $view = $this->getProfileSettingsUseCase->handle($projectId);

        return view('admin.settings.profile', ['view' => $view, 'memberCode' => $getMemberCode->handle($projectId)]);
    }

    public function update(SettingsProfileRequest $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $this->updateProfileSettingsUseCase->handle(
            $this->inputFactory->profileSettings($projectId, $request),
        );

        return redirect()->route('admin.settings.profile', $this->buildAdminRouteParams())
            ->with('success', 'プロフィール設定を更新しました');
    }
}
