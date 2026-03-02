<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Requests\Admin\SettingsRanksRequest;
use App\UseCases\Admin\Settings\GetRankSettingsUseCase;
use App\UseCases\Admin\Settings\UpdateRankSettingsUseCase;

class RanksController extends AdminBaseController
{
    public function __construct(
        private GetRankSettingsUseCase $getRankSettingsUseCase,
        private UpdateRankSettingsUseCase $updateRankSettingsUseCase
    ) {}

    public function index()
    {
        $projectId = $this->resolveAdminProjectId();
        $view = $this->getRankSettingsUseCase->handle($projectId);

        return view('admin.settings.ranks', ['view' => $view]);
    }

    public function update(SettingsRanksRequest $request)
    {
        $projectId = $this->resolveAdminProjectId();

        try {
            $this->updateRankSettingsUseCase->handle($projectId, $request->toPayload());
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['ranks' => $e->getMessage()]);
        }

        return redirect()->route('admin.settings.ranks', $this->buildAdminRouteParams())
            ->with('success', 'ランク・ポイント設定を更新しました');
    }
}
