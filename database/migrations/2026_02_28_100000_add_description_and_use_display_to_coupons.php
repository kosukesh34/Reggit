<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            if (!Schema::hasColumn('coupons', 'description')) {
                $table->text('description')->nullable()->after('name')->comment('会員に表示する説明文');
            }
            if (!Schema::hasColumn('coupons', 'use_display')) {
                $table->string('use_display', 20)->default('none')->after('theme_color')
                    ->comment('使用時表示: none=なし, barcode=バーコード, qr=QRコード');
            }
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn(['description', 'use_display']);
        });
    }
};
