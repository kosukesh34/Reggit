<?php

namespace App\UseCases\Admin\Settings;

use App\Models\AdminUser;

class FindStaffUseCase
{
    public function handle(int $projectId, int $staffId): AdminUser
    {
        return AdminUser::query()
            ->where('role', AdminUser::ROLE_STAFF)
            ->where(function ($query) use ($projectId) {
                $query->where('project_id', $projectId)
                    ->orWhereHas('projects', fn ($projects) => $projects->where('projects.id', $projectId));
            })
            ->findOrFail($staffId);
    }
}
