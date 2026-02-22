<?php

namespace App\UseCases\Admin\Account;

use App\Models\AdminUser;
use App\UseCases\Admin\Account\Data\AccountUpdateInput;

class AccountUpdateUseCase
{
    public function handle(int $id, AccountUpdateInput $input): AdminUser
    {
        $adminUser = AdminUser::findOrFail($id);

        if ($input->passwordFilled && $input->password !== null) {
            $adminUser->password = $input->password;
            $adminUser->save();
        }

        $adminUser->update($input->accountPayload);
        $adminUser->projects()->sync($input->projectIds);

        if ($adminUser->isStaff()) {
            $adminUser->syncPermissions($input->permissions);
        } else {
            $adminUser->permissions()->delete();
        }

        return $adminUser;
    }
}
