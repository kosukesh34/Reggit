<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 雨の日はお知らせの「スケジュール配信」の種類の1つにする。店舗・閾値は配信の params に持つため、
     * 専用のルール表をやめ、判定結果はスケジュール配信×営業日で1件にする。
     * 登録画面がまだ無く、ルールと判定結果は空のため作り直す。
     */
    public function up(): void
    {
        Schema::dropIfExists('weather_coupon_runs');
        Schema::dropIfExists('weather_coupon_rules');

        Schema::create('weather_coupon_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('after_registration_delivery_id')->constrained()->cascadeOnDelete();
            $table->date('business_date');
            $table->string('status', 20);
            $table->unsignedTinyInteger('max_precipitation_probability')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamp('approval_expires_at')->nullable();
            $table->foreignId('coupon_delivery_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('issued_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->unique(['after_registration_delivery_id', 'business_date'], 'weather_coupon_runs_delivery_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_coupon_runs');

        Schema::create('weather_coupon_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('min_precipitation_probability');
            $table->unsignedTinyInteger('min_rain_hours');
            $table->unsignedTinyInteger('check_hours_before_open');
            $table->unsignedTinyInteger('cutoff_hours_before_close');
            $table->unsignedTinyInteger('weekly_limit');
            $table->string('target', 20);
            $table->boolean('requires_approval')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['project_id', 'is_active']);
        });

        Schema::create('weather_coupon_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('weather_coupon_rule_id')->constrained()->cascadeOnDelete();
            $table->date('business_date');
            $table->string('status', 20);
            $table->unsignedTinyInteger('max_precipitation_probability')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamp('approval_expires_at')->nullable();
            $table->foreignId('coupon_delivery_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('issued_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->unique(['weather_coupon_rule_id', 'business_date']);
        });
    }
};
