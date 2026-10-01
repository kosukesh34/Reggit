<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Requests\Admin\SettingsMemberCodeRequest;
use App\UseCases\Admin\Settings\UpdateMemberCodeUseCase;

class MemberCodeController extends AdminBaseController
{
    public function update(SettingsMemberCodeRequest $request, UpdateMemberCodeUseCase $updateSettings)
    {
        $updateSettings->handle($request->toInput($this->resolveAdminProjectId()));

        return redirect()->to(admin_route('admin.settings.profile').'#profile-code')
            ->with('success', '会員番号の発行ルールを更新しました');
    }
}
