<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 内容を作るまで会員には何も表示しない機能なので、初期値は有効にしてメニューに出す
        Schema::table('project_feature_flags', function (Blueprint $table): void {
            $table->boolean('feature_surveys_enabled')->default(true)->after('feature_tickets_enabled');
            $table->boolean('feature_popups_enabled')->default(true)->after('feature_surveys_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('project_feature_flags', function (Blueprint $table): void {
            $table->dropColumn(['feature_surveys_enabled', 'feature_popups_enabled']);
        });
    }
};
