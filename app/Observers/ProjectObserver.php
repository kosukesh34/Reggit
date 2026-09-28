<?php

namespace App\Observers;

use App\Models\AdminUser;
use App\Models\Project;
use App\Models\Store;

class ProjectObserver
{
    public function created(Project $project): void
    {
        Store::create(['project_id' => $project->id, 'store_key' => 'main', 'name' => $project->name, 'is_active' => $project->is_active]);
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
