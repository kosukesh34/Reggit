<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Requests\Admin\LotterySettingsUpdateRequest;
use App\UseCases\Admin\Settings\GetLotterySettingsUseCase;
use App\UseCases\Admin\Settings\UpdateLotterySettingsUseCase;

class LotteryController extends AdminBaseController
{
    public function __construct(
        private GetLotterySettingsUseCase $getLotterySettingsUseCase,
        private UpdateLotterySettingsUseCase $updateLotterySettingsUseCase,
    ) {}

    public function index()
    {
        $projectId = $this->resolveAdminProjectId();
        $view = $this->getLotterySettingsUseCase->handle($projectId);

        return view('admin.settings.lottery', ['view' => $view]);
    }

    public function update(LotterySettingsUpdateRequest $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validated();
        $this->updateLotterySettingsUseCase->handle($projectId, [
            'gacha_enabled' => (bool) $validated['gacha_enabled'],
            'scratch_enabled' => (bool) $validated['scratch_enabled'],
            'roulette_enabled' => (bool) $validated['roulette_enabled'],
            'grant_trigger' => (string) $validated['grant_trigger'],
            'min_amount' => isset($validated['min_amount']) ? (float) $validated['min_amount'] : null,
            'entitlement_expires_hours' => isset($validated['entitlement_expires_hours'])
                ? (int) $validated['entitlement_expires_hours']
                : null,
            'prizes' => $validated['prizes'] ?? [],
        ]);

        return redirect()->route('admin.settings.lottery', $this->buildAdminRouteParams())
            ->with('success', '購入特典ゲームの設定を更新しました');
    }
}
