<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_business_settings', function (Blueprint $table) {
            $table->boolean('queue_require_membership')->default(false)->after('queue_avg_serve_minutes');
            $table->boolean('queue_channel_line')->default(true)->after('queue_require_membership');
            $table->boolean('queue_channel_native')->default(false)->after('queue_channel_line');
        });
    }

    public function down(): void
    {
        Schema::table('project_business_settings', function (Blueprint $table) {
            $table->dropColumn([
                'queue_require_membership',
                'queue_channel_line',
                'queue_channel_native',
            ]);
        });
    }
};
