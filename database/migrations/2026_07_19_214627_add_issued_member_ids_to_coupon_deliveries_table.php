<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupon_deliveries', function (Blueprint $table) {
            $table->json('issued_member_ids')->nullable()->after('target_member_ids');
        });
    }

    public function down(): void
    {
        Schema::table('coupon_deliveries', function (Blueprint $table) {
            $table->dropColumn('issued_member_ids');
        });
    }
};
