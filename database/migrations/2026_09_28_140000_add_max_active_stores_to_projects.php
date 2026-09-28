<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedInteger('max_active_stores')->default(1);
        });

        DB::table('projects')->select('id')->orderBy('id')->chunkById(100, function ($projects) {
            foreach ($projects as $project) {
                $active = DB::table('stores')->where('project_id', $project->id)->where('is_active', true)->count();
                DB::table('projects')->where('id', $project->id)->update(['max_active_stores' => max(1, $active)]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', fn (Blueprint $table) => $table->dropColumn('max_active_stores'));
    }
};
