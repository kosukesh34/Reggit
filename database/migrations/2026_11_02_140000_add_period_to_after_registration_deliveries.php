<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('after_registration_deliveries', function (Blueprint $table) {
            // 配信期間（開始日・終了日を含む）。空なら期限なし
            $table->date('starts_on')->nullable()->after('is_active');
            $table->date('ends_on')->nullable()->after('starts_on');
        });
    }

    public function down(): void
    {
        Schema::table('after_registration_deliveries', fn (Blueprint $table) => $table->dropColumn(['starts_on', 'ends_on']));
    }
};
