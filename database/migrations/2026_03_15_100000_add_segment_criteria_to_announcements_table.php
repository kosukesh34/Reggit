<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->json('segment_criteria')->nullable()->after('target_member_ids')
                ->comment('セグメント配信時の条件 { period_days, min_total_amount? } or { period_days, no_orders: true }');
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn('segment_criteria');
        });
    }
};
