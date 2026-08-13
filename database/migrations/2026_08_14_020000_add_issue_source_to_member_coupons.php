<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('member_coupons', 'issue_source_type')) {
            return;
        }

        Schema::table('member_coupons', function (Blueprint $table) {
            $table->string('issue_source_type', 32)->nullable()->after('order_id');
            $table->string('issue_source_id', 64)->nullable()->after('issue_source_type');
            $table->index(
                ['project_id', 'member_id', 'issue_source_type', 'issue_source_id'],
                'member_coupons_issue_source_index'
            );
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('member_coupons', 'issue_source_type') === false) {
            return;
        }

        Schema::table('member_coupons', function (Blueprint $table) {
            $table->dropIndex('member_coupons_issue_source_index');
            $table->dropColumn(['issue_source_type', 'issue_source_id']);
        });
    }
};
