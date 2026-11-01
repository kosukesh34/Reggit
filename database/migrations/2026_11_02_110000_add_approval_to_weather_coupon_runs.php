<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weather_coupon_runs', function (Blueprint $table) {
            // 承認制のルールで、この時刻までに承認されなければ配らない
            $table->timestamp('approval_expires_at')->nullable()->after('checked_at');
        });
    }

    public function down(): void
    {
        Schema::table('weather_coupon_runs', fn (Blueprint $table) => $table->dropColumn('approval_expires_at'));
    }
};
