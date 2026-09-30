<?php

namespace App\UseCases\Admin\Settings;

use App\Models\AdminUser;
use Illuminate\Database\Eloquent\Collection;

class GetStaffListUseCase
{
    /** @return Collection<int, AdminUser> */
    public function handle(int $projectId): Collection
    {
        return AdminUser::query()
            ->whereIn('role', [AdminUser::ROLE_STAFF, AdminUser::ROLE_ADMIN])
            ->where(function ($query) use ($projectId) {
                $query->where('project_id', $projectId)
                    ->orWhereHas('projects', fn ($projects) => $projects->where('projects.id', $projectId));
            })
            ->with(['permissions', 'stores'])
            ->orderBy('name')
            ->get();
    }
}
