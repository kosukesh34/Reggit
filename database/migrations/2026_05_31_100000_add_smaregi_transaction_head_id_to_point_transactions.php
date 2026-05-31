<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('point_transactions', function (Blueprint $table) {
            $table->string('smaregi_transaction_head_id', 32)->nullable()->after('order_id');
            $table->unique('smaregi_transaction_head_id', 'point_transactions_smaregi_tx_head_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('point_transactions', function (Blueprint $table) {
            $table->dropUnique('point_transactions_smaregi_tx_head_id_unique');
            $table->dropColumn('smaregi_transaction_head_id');
        });
    }
};
