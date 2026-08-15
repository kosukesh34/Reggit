<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_member_rank_settings', function (Blueprint $table) {
            $table->boolean('use_stamps')->default(true)->after('member_rank_basis');
            $table->boolean('use_transaction_stamps')->default(true)->after('use_stamps');
            $table->boolean('use_points')->default(true)->after('use_transaction_stamps');
            $table->string('member_rank_aggregation_type', 20)->default('total')->after('use_points');
            $table->unsignedInteger('member_rank_period_days')->default(365)->after('member_rank_aggregation_type');
            $table->boolean('is_rank_down_enabled')->default(false)->after('member_rank_period_days');
        });
    }

    public function down(): void
    {
        Schema::table('project_member_rank_settings', function (Blueprint $table) {
            $table->dropColumn([
                'use_stamps',
                'use_transaction_stamps',
                'use_points',
                'member_rank_aggregation_type',
                'member_rank_period_days',
                'is_rank_down_enabled',
            ]);
        });
    }
};
