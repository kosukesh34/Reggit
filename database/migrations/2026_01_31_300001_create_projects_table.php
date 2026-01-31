<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_key', 64)->unique()->comment('URL・API識別用の一意キー（英数字など）');
            $table->string('name')->comment('店舗・プロジェクト名');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('projects')->insert([
            'id' => 1,
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
        Schema::dropIfExists('projects');
    }
};
