<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** 配信期間を日付から日時にする。保存済みの期間は開始日 0:00〜終了日 23:59:59 として引き継ぐ */
    public function up(): void
    {
        Schema::table('after_registration_deliveries', function (Blueprint $table) {
            $table->dateTime('starts_at')->nullable()->after('is_active');
            $table->dateTime('ends_at')->nullable()->after('starts_at');
        });
        DB::table('after_registration_deliveries')->whereNotNull('starts_on')->update(['starts_at' => DB::raw('TIMESTAMP(starts_on)')]);
        DB::table('after_registration_deliveries')->whereNotNull('ends_on')->update(['ends_at' => DB::raw("TIMESTAMP(ends_on, '23:59:59')")]);
        Schema::table('after_registration_deliveries', fn (Blueprint $table) => $table->dropColumn(['starts_on', 'ends_on']));
    }

    public function down(): void
    {
        Schema::table('after_registration_deliveries', function (Blueprint $table) {
            $table->date('starts_on')->nullable()->after('is_active');
            $table->date('ends_on')->nullable()->after('starts_on');
        });
        DB::table('after_registration_deliveries')->whereNotNull('starts_at')->update(['starts_on' => DB::raw('DATE(starts_at)')]);
        DB::table('after_registration_deliveries')->whereNotNull('ends_at')->update(['ends_on' => DB::raw('DATE(ends_at)')]);
        Schema::table('after_registration_deliveries', fn (Blueprint $table) => $table->dropColumn(['starts_at', 'ends_at']));
    }
};
