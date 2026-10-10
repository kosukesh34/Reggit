<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_coupons', function (Blueprint $table): void {
            $table->dateTime('notification_requested_at')->nullable();
            $table->boolean('notification_pending')->default(false);
            $table->dateTime('notification_retry_at')->nullable();
            $table->index(['notification_pending', 'notification_retry_at'], 'member_coupon_notification_retry');
        });
    }

    public function down(): void
    {
        Schema::table('member_coupons', function (Blueprint $table): void {
            $table->dropIndex('member_coupon_notification_retry');
            $table->dropColumn(['notification_requested_at', 'notification_pending', 'notification_retry_at']);
        });
    }
};
