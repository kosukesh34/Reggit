<?php

namespace App\UseCases\Admin\Settings;

use App\Models\AdminUser;
use Illuminate\Support\Facades\DB;

class UpdateStaffUseCase
{
    /** @param array<string, mixed> $data */
    public function handle(AdminUser $staff, array $data, int $projectId): AdminUser
    {
        return DB::transaction(function () use ($staff, $data, $projectId) {
            $staff->role = $data['role'] ?? $staff->role;
            $staff->name = $data['name'];
            $staff->email = $data['email'];
            if (($data['password'] ?? null) !== null) {
                $staff->password = $data['password'];
            }
            $staff->save();
            if (array_key_exists('store_ids', $data)) {
                $removedIds = $staff->stores()->where('stores.project_id', $projectId)->whereNotIn('stores.id', $data['store_ids'])->pluck('stores.id')->all();
                $staff->stores()->detach($removedIds);
                $staff->stores()->syncWithoutDetaching(collect($data['store_ids'])->mapWithKeys(fn ($id) => [$id => ['project_id' => $projectId]])->all());
            }
            $staff->syncPermissions($staff->isStaff() ? ($data['permissions'] ?? []) : []);

            return $staff;
        });
    }
}
