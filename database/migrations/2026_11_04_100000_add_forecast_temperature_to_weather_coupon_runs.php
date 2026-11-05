<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** 気温の配信で判定に使った営業時間内の最高気温（以上の条件）か最低気温（以下の条件） */
    public function up(): void
    {
        Schema::table('weather_coupon_runs', function (Blueprint $table) {
            $table->decimal('forecast_temperature', 4, 1)->nullable()->after('max_precipitation_probability');
        });
    }

    public function down(): void
    {
        Schema::table('weather_coupon_runs', function (Blueprint $table) {
            $table->dropColumn('forecast_temperature');
        });
    }
};
