<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_visits', function (Blueprint $table) {
            $table->decimal('amount', 12, 2)->nullable()->after('note');
            $table->string('smaregi_transaction_head_id', 64)->nullable()->after('amount');
            $table->unique(
                ['project_id', 'smaregi_transaction_head_id'],
                'member_visits_project_smaregi_tx_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('member_visits', function (Blueprint $table) {
            $table->dropUnique('member_visits_project_smaregi_tx_unique');
            $table->dropColumn(['amount', 'smaregi_transaction_head_id']);
        });
    }
};
