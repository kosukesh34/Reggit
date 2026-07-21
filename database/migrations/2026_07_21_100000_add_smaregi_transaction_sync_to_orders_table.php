<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('smaregi_transaction_head_id', 64)->nullable()->after('smaregi_points_sync_started_at');
            $table->timestamp('smaregi_transaction_synced_at')->nullable()->after('smaregi_transaction_head_id');
            $table->timestamp('smaregi_transaction_sync_started_at')->nullable()->after('smaregi_transaction_synced_at');

            $table->unique(
                ['project_id', 'smaregi_transaction_head_id'],
                'orders_project_smaregi_transaction_head_id_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique('orders_project_smaregi_transaction_head_id_unique');
            $table->dropColumn([
                'smaregi_transaction_head_id',
                'smaregi_transaction_synced_at',
                'smaregi_transaction_sync_started_at',
            ]);
        });
    }
};
