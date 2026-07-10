<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('smaregi_points_synced_at')->nullable()->after('points_earned');
            $table->timestamp('smaregi_points_sync_started_at')->nullable()->after('smaregi_points_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['smaregi_points_synced_at', 'smaregi_points_sync_started_at']);
        });
    }
};
