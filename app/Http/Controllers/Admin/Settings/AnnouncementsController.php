<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\UseCases\Admin\Settings\GetAnnouncementsSettingsUseCase;
use App\UseCases\Admin\Settings\UpdateAnnouncementsSettingsUseCase;
use Illuminate\Http\Request;

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

        return view('admin.settings.announcements', [
            'featureEnabled' => $view->featureEnabled,
            'defaultPinned' => $view->defaultPinned,
            'defaultExpiryDays' => $view->defaultExpiryDays,
        ]);
    }

    public function update(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validate([
            'feature_announcements_enabled' => 'nullable|boolean',
            'announcement_default_pinned' => 'nullable|boolean',
            'announcement_default_expiry_days' => 'nullable|integer|min:0|max:365',
        ]);
        $this->updateAnnouncementsSettingsUseCase->handle($projectId, $validated);

        return redirect()->route('admin.settings.announcements', $this->buildAdminRouteParams())
            ->with('success', 'お知らせの設定を更新しました');
    }
}
