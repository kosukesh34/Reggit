<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('after_registration_deliveries', function (Blueprint $table) {
            if (! Schema::hasColumn('after_registration_deliveries', 'category')) {
                $table->string('category', 32)->default('condition')->after('project_id');
            }
            if (! Schema::hasColumn('after_registration_deliveries', 'schedule_type')) {
                $table->string('schedule_type', 64)->default('new_user_push_1')->after('category');
            }
            if (! Schema::hasColumn('after_registration_deliveries', 'content_type')) {
                $table->string('content_type', 32)->default('announcement')->after('schedule_type');
            }
            if (! Schema::hasColumn('after_registration_deliveries', 'send_notification')) {
                $table->boolean('send_notification')->default(true)->after('coupon_id');
            }
            if (! Schema::hasColumn('after_registration_deliveries', 'push_hour')) {
                $table->unsignedTinyInteger('push_hour')->default(10)->after('send_notification');
            }
            if (! Schema::hasColumn('after_registration_deliveries', 'push_minute')) {
                $table->unsignedTinyInteger('push_minute')->default(0)->after('push_hour');
            }
            if (! Schema::hasColumn('after_registration_deliveries', 'params')) {
                $table->json('params')->nullable()->after('push_minute');
            }
        });

        DB::table('after_registration_deliveries')->orderBy('id')->get()->each(function (object $row): void {
            $params = is_string($row->params ?? null) ? json_decode($row->params, true) : ($row->params ?? null);
            if (! is_array($params)) {
                $params = [];
            }
            $params['delay_minutes'] = (int) ($row->delay_minutes ?? 30);
            DB::table('after_registration_deliveries')->where('id', $row->id)->update([
                'category' => 'condition',
                'schedule_type' => 'new_user_push_1',
                'content_type' => 'announcement',
                'params' => json_encode($params),
            ]);
        });

        Schema::create('scheduled_delivery_sends', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('after_registration_delivery_id');
            $table->unsignedBigInteger('member_id');
            $table->string('send_key', 32);
            $table->timestamp('sent_at');
            $table->timestamps();
            $table->unique(['after_registration_delivery_id', 'member_id', 'send_key'], 'scheduled_delivery_sends_unique');
            $table->index('member_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_delivery_sends');
        Schema::table('after_registration_deliveries', function (Blueprint $table) {
            foreach (['category', 'schedule_type', 'content_type', 'send_notification', 'push_hour', 'push_minute', 'params'] as $column) {
                if (Schema::hasColumn('after_registration_deliveries', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
