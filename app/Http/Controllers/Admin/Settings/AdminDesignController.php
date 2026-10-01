<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Requests\Admin\SettingsAdminDesignRequest;
use App\UseCases\Admin\Settings\GetAdminDesignSettingsUseCase;
use App\UseCases\Admin\Settings\UpdateAdminDesignUseCase;

class AdminDesignController extends AdminBaseController
{
    public function index(GetAdminDesignSettingsUseCase $getSettings)
    {
        return view('admin.settings.features', [
            'view' => $getSettings->handle($this->resolveAdminProjectId()),
            'featureSection' => 'admin',
            'featureRoute' => 'admin.settings.admin-design',
        ]);
    }

    public function update(SettingsAdminDesignRequest $request, UpdateAdminDesignUseCase $updateSettings)
    {
        $updateSettings->handle($request->toInput($this->resolveAdminProjectId()));

        return redirect()->route('admin.settings.admin-design', $this->buildAdminRouteParams())
            ->with('success', '管理画面デザインを更新しました');
    }
}
