<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Requests\Admin\SettingsShopRequest;
use App\UseCases\Admin\Settings\GetShopSettingsUseCase;
use App\UseCases\Admin\Settings\UpdateShopSettingsUseCase;

class ShopController extends AdminBaseController
{
    public function __construct(
        private GetShopSettingsUseCase $getShopSettingsUseCase,
        private UpdateShopSettingsUseCase $updateShopSettingsUseCase,
    ) {}

    public function index()
    {
        $view = $this->getShopSettingsUseCase->handle($this->resolveAdminProjectId());

        return view('admin.settings.shop', ['view' => $view]);
    }

    public function update(SettingsShopRequest $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $this->updateShopSettingsUseCase->handle($request->toInput($projectId));

        return redirect()->route('admin.settings.shop', $this->buildAdminRouteParams())
            ->with('success', 'ショップ設定を更新しました');
    }
}
