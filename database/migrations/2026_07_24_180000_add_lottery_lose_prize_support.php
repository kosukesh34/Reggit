<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lottery_prizes')) {
            return;
        }

        Schema::table('lottery_prizes', function (Blueprint $table) {
            if (! Schema::hasColumn('lottery_prizes', 'is_lose')) {
                $table->boolean('is_lose')->default(false)->after('coupon_id');
            }
        });

        // coupon_id を nullable に（ハズレはクーポン不要）
        Schema::table('lottery_prizes', function (Blueprint $table) {
            $table->dropForeign(['coupon_id']);
        });
        Schema::table('lottery_prizes', function (Blueprint $table) {
            $table->unsignedBigInteger('coupon_id')->nullable()->change();
            $table->foreign('coupon_id')->references('id')->on('coupons')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('lottery_prizes')) {
            return;
        }

        Schema::table('lottery_prizes', function (Blueprint $table) {
            $table->dropForeign(['coupon_id']);
        });
        Schema::table('lottery_prizes', function (Blueprint $table) {
            $table->unsignedBigInteger('coupon_id')->nullable(false)->change();
            $table->foreign('coupon_id')->references('id')->on('coupons')->cascadeOnDelete();
            if (Schema::hasColumn('lottery_prizes', 'is_lose')) {
                $table->dropColumn('is_lose');
            }
        });
    }
};
