<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->boolean('notification_pending')->default(false);
            $table->timestamp('notification_retry_at')->nullable();
            $table->index(['notification_pending', 'notification_retry_at']);
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropIndex(['notification_pending', 'notification_retry_at']);
            $table->dropColumn(['notification_pending', 'notification_retry_at']);
        });
    }
};
