<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_business_settings', function (Blueprint $table): void {
            $table->boolean('reservation_capacity_by_people')->default(false)->after('reservation_capacity_per_slot')->comment('枠の上限を人数で数える。falseは予約件数');
            $table->unsignedSmallInteger('reservation_max_party_size')->default(10)->after('reservation_capacity_by_people')->comment('1件の予約で選べる最大人数');
            $table->boolean('reservation_same_day')->default(false)->after('reservation_max_party_size')->comment('当日の予約を受け付ける');
            $table->unsignedSmallInteger('reservation_lead_minutes')->default(0)->after('reservation_same_day')->comment('予約時刻の何分前まで受け付けるか');
            $table->unsignedSmallInteger('reservation_change_deadline_hours')->default(0)->after('reservation_lead_minutes')->comment('会員が変更・キャンセルできるのは予約時刻の何時間前までか');
        });
    }

    public function down(): void
    {
        Schema::table('project_business_settings', function (Blueprint $table): void {
            $table->dropColumn(['reservation_capacity_by_people', 'reservation_max_party_size', 'reservation_same_day', 'reservation_lead_minutes', 'reservation_change_deadline_hours']);
        });
    }
};
