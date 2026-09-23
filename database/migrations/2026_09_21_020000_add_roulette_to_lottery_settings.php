<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_lottery_settings', function (Blueprint $table) {
            $table->boolean('roulette_enabled')->default(false)->after('scratch_enabled');
        });
        Schema::table('project_feature_flags', function (Blueprint $table) {
            $table->boolean('feature_roulette_enabled')->default(false)->after('feature_scratch_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('project_lottery_settings', function (Blueprint $table) {
            $table->dropColumn('roulette_enabled');
        });
        Schema::table('project_feature_flags', function (Blueprint $table) {
            $table->dropColumn('feature_roulette_enabled');
        });
    }
};
