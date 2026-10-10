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
            $synced = $this->updateRankSettingsUseCase->handle($request->toInput($projectId));
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['ranks' => $e->getMessage()]);
        } catch (\RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.settings.ranks', $this->buildAdminRouteParams())
            ->with('success', $synced
                ? 'ランク・ポイント・スタンプを更新しました'
                : 'ランク・ポイント・スタンプを保存しました。スマレジへの反映は自動再送します。');
    }
}
