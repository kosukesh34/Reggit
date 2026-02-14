<?php

namespace App\UseCases\Admin\Account;

use App\Models\AdminUser;
use App\UseCases\Admin\Account\Dto\AccountDestroyResultDto;

class AccountDestroyUseCase
{
    public function handle(AdminUser $me, int $id): AccountDestroyResultDto
    {
        $adminUser = AdminUser::findOrFail($id);

        if ($adminUser->id === $me->id) {
            return new AccountDestroyResultDto(false, '自分自身のアカウントは削除できません。');
        }

        if (!$me->canEditUser($adminUser)) {
            abort(403, 'このアカウントを削除する権限がありません。');
        }

        $adminUser->permissions()->delete();
        $adminUser->delete();

        return new AccountDestroyResultDto(true, 'アカウントを削除しました');
    }
}
