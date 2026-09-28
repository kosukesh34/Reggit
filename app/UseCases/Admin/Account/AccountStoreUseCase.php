<?php

namespace App\UseCases\Admin\Account;

use App\Models\AdminUser;
use App\Models\Store;
use App\UseCases\Admin\Account\Data\AccountStoreInput;

class AccountStoreUseCase
{
    public function handle(AccountStoreInput $input): AdminUser
    {
        $admin = AdminUser::create($input->accountPayload);

        if (count($input->projectIds) > 0) {
            $admin->projects()->sync($input->projectIds);
        }

        $stores = Store::whereIn('project_id', $input->projectIds)->where('store_key', 'main')->get();
        $admin->stores()->sync($stores->mapWithKeys(fn ($store) => [$store->id => ['project_id' => $store->project_id]])->all());

        if ($admin->isStaff()) {
            $admin->syncPermissions($input->permissions);
        }

        return $admin;
    }
}
