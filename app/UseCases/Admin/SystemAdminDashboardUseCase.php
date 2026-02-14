<?php

namespace App\UseCases\Admin;

use App\Models\AdminUser;
use App\Models\ApiToken;
use App\Models\Project;
use App\UseCases\Admin\Dto\SystemAdminDashboardDto;

class SystemAdminDashboardUseCase
{
    public function handle(): SystemAdminDashboardDto
    {
        return new SystemAdminDashboardDto(
            Project::where('is_active', true)->count(),
            AdminUser::count(),
            ApiToken::where('is_active', true)->count()
        );
    }
}
