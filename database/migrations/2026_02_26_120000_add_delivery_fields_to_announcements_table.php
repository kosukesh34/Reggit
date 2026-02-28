<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->string('delivery_type', 32)->default('immediate_bulk')->after('is_pinned')
                ->comment('immediate_bulk=一斉, personal=個人宛, scheduled=予約配信');
            $table->timestamp('scheduled_at')->nullable()->after('delivery_type');
            $table->timestamp('sent_at')->nullable()->after('scheduled_at');
            $table->json('target_member_ids')->nullable()->after('sent_at')->comment('個人宛時は1件、一斉時はnull=全員');
            $table->string('delivery_status', 24)->default('draft')->after('target_member_ids')
                ->comment('draft=下書き, sent=配信済, scheduled=予約中');
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_type',
                'scheduled_at',
                'sent_at',
                'target_member_ids',
                'delivery_status',
            ]);
        });
    }
};
