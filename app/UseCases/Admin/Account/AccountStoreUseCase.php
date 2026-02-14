<?php

namespace App\UseCases\Admin\Account;

use App\Models\AdminUser;
use App\UseCases\Admin\Account\Dto\AccountStoreInputDto;

class AccountStoreUseCase
{
    public function handle(AccountStoreInputDto $input): AdminUser
    {
        $admin = AdminUser::create($input->accountPayload);

        if (count($input->projectIds) > 0) {
            $admin->projects()->sync($input->projectIds);
        }

        if ($admin->isStaff()) {
            $admin->syncPermissions($input->permissions);
        }

        return $admin;
    }
}
