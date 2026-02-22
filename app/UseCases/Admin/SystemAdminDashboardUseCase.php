<?php

namespace App\UseCases\Admin;

use App\Models\AdminUser;
use App\Models\ApiToken;
use App\Models\Project;
use App\UseCases\Admin\Data\SystemAdminDashboardView;

class SystemAdminDashboardUseCase
{
    public function handle(): SystemAdminDashboardView
    {
        return new SystemAdminDashboardView(
            Project::where('is_active', true)->count(),
            AdminUser::count(),
            ApiToken::where('is_active', true)->count()
        );
    }
}
