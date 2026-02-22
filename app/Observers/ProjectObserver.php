<?php

namespace App\Observers;

use App\Models\AdminUser;
use App\Models\Project;

class ProjectObserver
{
    public function created(Project $project): void
    {
        $systemAdmins = AdminUser::where('role', AdminUser::ROLE_SYSTEM_ADMIN)->get();
        foreach ($systemAdmins as $adminUser) {
            $adminUser->projects()->syncWithoutDetaching([$project->id]);
        }
    }

    public function updated(Project $project): void
    {
        // reserved for future hooks
    }
}
