<?php

namespace App\UseCases\Admin\Settings;

use App\Models\AdminUser;
use Illuminate\Support\Facades\DB;

class UpdateStaffUseCase
{
    /** @param array<string, mixed> $data */
    public function handle(AdminUser $staff, array $data): AdminUser
    {
        return DB::transaction(function () use ($staff, $data) {
            $staff->name = $data['name'];
            $staff->email = $data['email'];
            if (($data['password'] ?? null) !== null) {
                $staff->password = $data['password'];
            }
            $staff->save();
            $staff->syncPermissions($data['permissions'] ?? []);

            return $staff;
        });
    }
}
