<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_member_rank_settings', function (Blueprint $table) {
            // ポイントを使える場所（app: アプリの注文 / store: お店のレジ / both）。アプリの案内文に使う
            $table->string('point_usage_scope', 16)->default('app')->after('use_points');
        });
    }

    public function down(): void
    {
        Schema::table('project_member_rank_settings', function (Blueprint $table) {
            $table->dropColumn('point_usage_scope');
        });
    }
};
