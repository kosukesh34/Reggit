<?php

namespace App\UseCases\Admin\Settings;

use App\Models\AdminUser;
use Illuminate\Support\Facades\DB;

class StoreStaffUseCase
{
    /** @param array<string, mixed> $data */
    public function handle(int $projectId, int $creatorId, array $data): AdminUser
    {
        return DB::transaction(function () use ($projectId, $creatorId, $data) {
            $staff = AdminUser::create([
                'name' => $data['name'],
                'login_id' => $data['login_id'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => AdminUser::ROLE_STAFF,
                'project_id' => $projectId,
                'created_by_id' => $creatorId,
            ]);
            $staff->projects()->sync([$projectId]);
            $staff->syncPermissions($data['permissions'] ?? []);

            return $staff;
        });
    }
}
