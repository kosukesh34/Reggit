<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->unsignedInteger('lifetime_granted_points')->default(0)->after('points')
                ->comment('累計付与ポイント（獲得・正の調整の合計。取消で減算）');
        });

        // 既存台帳からバックフィル
        DB::statement("
            UPDATE members m
            SET lifetime_granted_points = COALESCE((
                SELECT SUM(
                    CASE
                        WHEN pt.type = 'earned' AND pt.points > 0 THEN pt.points
                        WHEN pt.type = 'adjusted' AND pt.points > 0 THEN pt.points
                        WHEN pt.type = 'reversed' THEN -ABS(pt.points)
                        ELSE 0
                    END
                )
                FROM point_transactions pt
                WHERE pt.member_id = m.id
            ), 0)
        ");

        DB::statement('UPDATE members SET lifetime_granted_points = 0 WHERE lifetime_granted_points < 0');
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('lifetime_granted_points');
        });
    }
};
