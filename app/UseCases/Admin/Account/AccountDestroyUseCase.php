<?php

namespace App\UseCases\Admin\Account;

use App\Models\AdminUser;
use App\UseCases\Admin\Account\Data\AccountDestroyOutput;

class AccountDestroyUseCase
{
    public function handle(AdminUser $me, int $id): AccountDestroyOutput
    {
        $adminUser = AdminUser::findOrFail($id);

        if ($adminUser->id === $me->id) {
            return new AccountDestroyOutput(false, '自分自身のアカウントは削除できません。');
        }

        if ($me->canEditUser($adminUser) === false) {
            abort(403, 'このアカウントを削除する権限がありません。');
        }

        $adminUser->permissions()->delete();
        $adminUser->delete();

        return new AccountDestroyOutput(true, 'アカウントを削除しました');
    }
}
