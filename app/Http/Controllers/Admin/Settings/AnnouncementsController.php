<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Requests\Admin\SettingsAnnouncementsRequest;
use App\UseCases\Admin\Settings\GetAnnouncementsSettingsUseCase;
use App\UseCases\Admin\Settings\UpdateAnnouncementsSettingsUseCase;

class AnnouncementsController extends AdminBaseController
{
    private GetAnnouncementsSettingsUseCase $getAnnouncementsSettingsUseCase;

    private UpdateAnnouncementsSettingsUseCase $updateAnnouncementsSettingsUseCase;

    public function __construct(
        GetAnnouncementsSettingsUseCase $getAnnouncementsSettingsUseCase,
        UpdateAnnouncementsSettingsUseCase $updateAnnouncementsSettingsUseCase
    ) {
        $this->getAnnouncementsSettingsUseCase = $getAnnouncementsSettingsUseCase;
        $this->updateAnnouncementsSettingsUseCase = $updateAnnouncementsSettingsUseCase;
    }

    public function index()
    {
        $projectId = $this->resolveAdminProjectId();
        $view = $this->getAnnouncementsSettingsUseCase->handle($projectId);

        return view('admin.settings.announcements', ['view' => $view]);
    }

    public function update(SettingsAnnouncementsRequest $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $this->updateAnnouncementsSettingsUseCase->handle($request->toInput($projectId));

        return redirect()->route('admin.settings.announcements', $this->buildAdminRouteParams())
            ->with('success', 'お知らせの設定を更新しました');
    }
}
