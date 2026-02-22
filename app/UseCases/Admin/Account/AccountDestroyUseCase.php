<?php

namespace App\UseCases\Admin\Account;

use App\Models\AdminUser;
use App\UseCases\Admin\Account\Data\AccountDestroyResult;

class AccountDestroyUseCase
{
    public function handle(AdminUser $me, int $id): AccountDestroyResult
    {
        $adminUser = AdminUser::findOrFail($id);

        if ($adminUser->id === $me->id) {
            return new AccountDestroyResult(false, '自分自身のアカウントは削除できません。');
        }

        if ($me->canEditUser($adminUser) === false) {
            abort(403, 'このアカウントを削除する権限がありません。');
        }

        $adminUser->permissions()->delete();
        $adminUser->delete();

        return new AccountDestroyResult(true, 'アカウントを削除しました');
    }
}
