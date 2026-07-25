<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_line_liff_settings', function (Blueprint $table) {
            $table->boolean('order_line_notify_enabled')->default(true)->after('order_mode');
            $table->string('kitchen_access_token', 64)->nullable()->after('order_line_notify_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('project_line_liff_settings', function (Blueprint $table) {
            $table->dropColumn(['order_line_notify_enabled', 'kitchen_access_token']);
        });
    }
};
