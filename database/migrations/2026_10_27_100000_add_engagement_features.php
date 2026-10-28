<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surveys', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title', 120);
            $table->text('description')->nullable();
            $table->json('questions')->comment('[{type: nps|choice|text, label, options?, required}]');
            $table->unsignedInteger('reward_points')->default(0);
            $table->unsignedTinyInteger('send_after_visit_days')->nullable()->comment('来店のN日後に回答依頼を自動送信。NULLは送信しない');
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('survey_responses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->json('answers')->nullable();
            $table->unsignedTinyInteger('nps_score')->nullable();
            $table->timestamp('invited_at')->nullable()->comment('回答依頼を送った日時');
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();
            $table->unique(['survey_id', 'member_id']);
        });

        Schema::table('reservations', function (Blueprint $table): void {
            $table->timestamp('reminder_sent_at')->nullable()->after('status');
        });
        Schema::table('project_business_settings', function (Blueprint $table): void {
            $table->unsignedSmallInteger('reservation_reminder_hours')->default(0)->after('reservation_capacity_per_slot')->comment('予約の何時間前にリマインドするか。0は送らない');
        });

        Schema::create('acquisition_sources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);
            $table->string('code', 16)->unique();
            $table->timestamps();
        });

        Schema::table('members', function (Blueprint $table): void {
            $table->foreignId('acquisition_source_id')->nullable()->after('customer_store_id')->constrained()->nullOnDelete();
        });

        Schema::table('announcements', function (Blueprint $table): void {
            $table->string('action_path', 255)->nullable()->after('body')->comment('アプリ内の遷移先（例: /survey?survey=1）');
            $table->string('action_label', 40)->nullable()->after('action_path');
        });

        Schema::create('promo_popups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title', 120);
            $table->text('body')->nullable();
            $table->string('image_url', 500)->nullable();
            $table->string('display_type', 20)->comment('fullscreen|modal|bottom_sheet|banner');
            $table->string('button_label', 40)->nullable();
            $table->string('link_url', 500)->nullable()->comment('アプリ内パス（/coupon）または https URL');
            $table->string('frequency', 20)->comment('once|daily|every_open');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->index(['project_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_popups');
        Schema::table('announcements', function (Blueprint $table): void {
            $table->dropColumn(['action_path', 'action_label']);
        });
        Schema::table('members', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('acquisition_source_id');
        });
        Schema::dropIfExists('acquisition_sources');
        Schema::table('project_business_settings', function (Blueprint $table): void {
            $table->dropColumn('reservation_reminder_hours');
        });
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropColumn('reminder_sent_at');
        });
        Schema::dropIfExists('survey_responses');
        Schema::dropIfExists('surveys');
    }
};
