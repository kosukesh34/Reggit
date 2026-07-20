<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $project = DB::table('projects')->where('project_key', 'default')->first();
        if ($project === null) {
            return;
        }

        $newKey = $this->resolveNewProjectKey((int) $project->id);
        if ($newKey === 'default' || $newKey === '') {
            return;
        }
        if (DB::table('projects')->where('project_key', $newKey)->exists()) {
            return;
        }

        DB::table('projects')
            ->where('id', $project->id)
            ->update([
                'project_key' => $newKey,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // 元キーが環境依存のため down では default への復元のみ（衝突時は何もしない）
        if (DB::table('projects')->where('project_key', 'default')->exists()) {
            return;
        }

        $project = null;
        if (Schema::hasTable('project_profile_smaregi_settings')) {
            $projectId = DB::table('project_profile_smaregi_settings')
                ->where('smaregi_contract_id', 'sb_skp835t6')
                ->value('project_id');
            if (is_numeric($projectId)) {
                $project = DB::table('projects')->where('id', (int) $projectId)->first();
            }
        }
        if ($project === null) {
            $project = DB::table('projects')->where('project_key', 'like', 'project-%')->orderBy('id')->first();
        }
        if ($project === null) {
            return;
        }

        DB::table('projects')
            ->where('id', $project->id)
            ->update([
                'project_key' => 'default',
                'updated_at' => now(),
            ]);
    }

    private function resolveNewProjectKey(int $projectId): string
    {
        if (Schema::hasTable('project_profile_smaregi_settings')) {
            $contractId = DB::table('project_profile_smaregi_settings')
                ->where('project_id', $projectId)
                ->value('smaregi_contract_id');
            if (is_string($contractId)) {
                $trimmed = trim($contractId);
                if ($trimmed !== '' && preg_match('/^[a-zA-Z0-9_-]+$/', $trimmed) === 1) {
                    return $trimmed;
                }
            }
        }

        return 'project-'.$projectId;
    }
};
