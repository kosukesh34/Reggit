<?php

use App\Models\AdminUser;
use App\Models\Project;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $systemAdminIds = AdminUser::where('role', AdminUser::ROLE_SYSTEM_ADMIN)->pluck('id')->all();
        $projectIds = Project::pluck('id')->all();

        if ($systemAdminIds === [] || $projectIds === []) {
            return;
        }

        $now = now();
        $rows = [];
        foreach ($systemAdminIds as $adminUserId) {
            foreach ($projectIds as $projectId) {
                $rows[] = [
                    'admin_user_id' => $adminUserId,
                    'project_id' => $projectId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('admin_user_projects')->upsert(
            $rows,
            ['admin_user_id', 'project_id'],
            ['updated_at']
        );
    }

    public function down(): void
    {
        // no-op
    }
};
