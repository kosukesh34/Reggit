<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('projects')->where('project_key', 'default')->exists();
        if ($exists) {
            return;
        }

        DB::table('projects')->insert([
            'project_key' => 'default',
            'name' => 'デフォルト店舗',
            'description' => '複数店舗対応前の既存データ用',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('projects')->where('project_key', 'default')->delete();
    }
};
