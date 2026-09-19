<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_admin_ui_settings', function (Blueprint $table) {
            $table->string('navigation_header_background_color', 7)->default('#FFFFFF')->after('navigation_header_image_url');
            $table->string('home_image_url')->nullable()->after('navigation_header_background_color');
            $table->boolean('member_show_rank_progress')->default(true)->after('home_menu_layout');
            $table->boolean('member_show_point_history')->default(true)->after('member_show_rank_progress');
            $table->boolean('member_show_coupon_activity')->default(true)->after('member_show_point_history');
        });
    }

    public function down(): void
    {
        Schema::table('project_admin_ui_settings', function (Blueprint $table) {
            $table->dropColumn([
                'navigation_header_background_color',
                'home_image_url',
                'member_show_rank_progress',
                'member_show_point_history',
                'member_show_coupon_activity',
            ]);
        });
    }
};
