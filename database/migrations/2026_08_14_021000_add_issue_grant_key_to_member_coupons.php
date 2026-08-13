<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('member_coupons', 'issue_grant_key')) {
            return;
        }

        Schema::table('member_coupons', function (Blueprint $table) {
            $table->string('issue_grant_key', 64)->nullable()->after('issue_source_id');
            $table->unique(
                ['project_id', 'member_id', 'issue_grant_key'],
                'member_coupons_grant_key_unique'
            );
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('member_coupons', 'issue_grant_key') === false) {
            return;
        }

        Schema::table('member_coupons', function (Blueprint $table) {
            $table->dropUnique('member_coupons_grant_key_unique');
            $table->dropColumn('issue_grant_key');
        });
    }
};
