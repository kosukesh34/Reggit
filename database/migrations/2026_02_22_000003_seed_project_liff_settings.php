<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('projects') || !Schema::hasTable('shop_settings')) {
            return;
        }
        if (!Schema::hasColumn('shop_settings', 'project_id')) {
            return;
        }

        $defaultLiffId = trim((string) env('LINE_LIFF_ID', ''));
        $defaultLiffUrl = trim((string) env('LINE_LIFF_URL', ''));

        $projects = DB::table('projects')->select('id')->get();
        foreach ($projects as $project) {
            $projectId = (int) $project->id;
            $this->upsertSetting($projectId, 'line_liff_id', $defaultLiffId);
            $this->upsertSetting($projectId, 'line_liff_url', $defaultLiffUrl);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('shop_settings')) {
            return;
        }

        DB::table('shop_settings')
            ->whereIn('key', ['line_liff_id', 'line_liff_url'])
            ->delete();
    }

    private function upsertSetting(int $projectId, string $key, string $value): void
    {
        $exists = DB::table('shop_settings')
            ->where('project_id', $projectId)
            ->where('key', $key)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('shop_settings')->insert([
            'project_id' => $projectId,
            'key' => $key,
            'value' => $value,
            'type' => 'string',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
