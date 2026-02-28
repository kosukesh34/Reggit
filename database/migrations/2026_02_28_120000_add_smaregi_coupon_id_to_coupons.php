<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            if (!Schema::hasColumn('coupons', 'smaregi_coupon_id')) {
                $table->string('smaregi_coupon_id', 64)->nullable()->after('stamp_card_id')
                    ->comment('スマレジPOSに登録したクーポンID（同期用）');
            }
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn('smaregi_coupon_id');
        });
    }
};
