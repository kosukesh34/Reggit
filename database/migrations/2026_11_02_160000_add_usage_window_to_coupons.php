<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            // 使える曜日（0=日〜6=土、"holiday"=祝日）と時間帯（[{start, end}]）。空なら制限なし
            $table->json('usable_days')->nullable()->after('end_at');
            $table->json('usable_time_ranges')->nullable()->after('usable_days');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', fn (Blueprint $table) => $table->dropColumn(['usable_days', 'usable_time_ranges']));
    }
};
