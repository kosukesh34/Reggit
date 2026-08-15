<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupon_deliveries', function (Blueprint $table) {
            $table->boolean('send_notification')->default(true)->after('issued_count');
        });
    }

    public function down(): void
    {
        Schema::table('coupon_deliveries', function (Blueprint $table) {
            $table->dropColumn('send_notification');
        });
    }
};
