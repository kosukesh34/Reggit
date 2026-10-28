<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acquisition_sources', function (Blueprint $table): void {
            $table->string('landing_tab', 30)->default('home')->after('code')->comment('QRで開くアプリの画面（タブキー）');
        });

        Schema::table('reservations', function (Blueprint $table): void {
            $table->timestamp('second_reminder_sent_at')->nullable()->after('reminder_sent_at');
        });
        Schema::table('project_business_settings', function (Blueprint $table): void {
            $table->unsignedSmallInteger('reservation_reminder_second_hours')->default(0)->after('reservation_reminder_hours')->comment('2回目のリマインドを何時間前に送るか。0は送らない');
            $table->text('reservation_reminder_message')->nullable()->after('reservation_reminder_second_hours')->comment('リマインドの本文。{店舗}{日時}{人数}{コース}を置き換える');
        });

        Schema::create('promo_popup_stats', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('promo_popup_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('secondary_clicks')->default(0);
            $table->unsignedInteger('closes')->default(0);
            $table->timestamps();
            $table->unique(['promo_popup_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_popup_stats');
        Schema::table('project_business_settings', function (Blueprint $table): void {
            $table->dropColumn(['reservation_reminder_second_hours', 'reservation_reminder_message']);
        });
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropColumn('second_reminder_sent_at');
        });
        Schema::table('acquisition_sources', function (Blueprint $table): void {
            $table->dropColumn('landing_tab');
        });
    }
};
