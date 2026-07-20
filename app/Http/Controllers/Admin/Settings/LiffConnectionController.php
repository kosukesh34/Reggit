<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Requests\Admin\LiffConnectionSettingsRequest;
use App\UseCases\Admin\Settings\GetLiffConnectionSettingsUseCase;
use App\UseCases\Admin\Settings\UpdateLiffConnectionSettingsUseCase;

class LiffConnectionController extends AdminBaseController
{
    public function __construct(
        private readonly GetLiffConnectionSettingsUseCase $getLiffConnectionSettingsUseCase,
        private readonly UpdateLiffConnectionSettingsUseCase $updateLiffConnectionSettingsUseCase,
    ) {}

    public function index()
    {
        $view = $this->getLiffConnectionSettingsUseCase->handle($this->resolveAdminProjectId());

        return view('admin.settings.liff-connection', ['view' => $view]);
    }

    public function update(LiffConnectionSettingsRequest $request)
    {
        $this->updateLiffConnectionSettingsUseCase->handle(
            $this->resolveAdminProjectId(),
            $request->toPayload(),
        );

        return redirect()->route('admin.settings.liff-connection', $this->buildAdminRouteParams())
            ->with('success', 'LIFF設定を更新しました');
    }
}
