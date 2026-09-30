<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Requests\Admin\SettingsMemberCardRequest;
use App\UseCases\Admin\Settings\GetMemberCardSettingsUseCase;
use App\UseCases\Admin\Settings\UpdateMemberCardUseCase;

class MemberCardController extends AdminBaseController
{
    public function index(GetMemberCardSettingsUseCase $getSettings)
    {
        return view('admin.settings.member-card', ['view' => $getSettings->handle($this->resolveAdminProjectId())]);
    }

    public function update(SettingsMemberCardRequest $request, UpdateMemberCardUseCase $updateSettings)
    {
        $updateSettings->handle($request->toInput($this->resolveAdminProjectId()));

        return redirect()->route('admin.settings.member-card', $this->buildAdminRouteParams())
            ->with('success', '会員証デザインを更新しました');
    }
}
