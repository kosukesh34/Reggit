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
            ->where('role', AdminUser::ROLE_STAFF)
            ->where(function ($query) use ($projectId) {
                $query->where('project_id', $projectId)
                    ->orWhereHas('projects', fn ($projects) => $projects->where('projects.id', $projectId));
            })
            ->with('permissions')
            ->orderBy('name')
            ->get();
    }
}
