<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Requests\Admin\LiffConnectionSettingsRequest;
use App\UseCases\Admin\Settings\UpdateLiffConnectionSettingsUseCase;
use Illuminate\Http\RedirectResponse;

class LiffConnectionController extends AdminBaseController
{
    public function __construct(
        private readonly UpdateLiffConnectionSettingsUseCase $updateLiffConnectionSettingsUseCase,
    ) {}

    public function index(): RedirectResponse
    {
        return redirect()
            ->route('admin.settings.home', $this->buildAdminRouteParams())
            ->with('success', 'LINE専用の設定画面は廃止しました。ホーム・メニューがSkipとLINEの共通画面です。');
    }

    public function update(LiffConnectionSettingsRequest $request)
    {
        $this->updateLiffConnectionSettingsUseCase->handle(
            $this->resolveAdminProjectId(),
            $request->toPayload(),
        );

        return redirect()->route('admin.settings.home', $this->buildAdminRouteParams())
            ->with('success', 'LIFF設定を更新しました');
    }
}
