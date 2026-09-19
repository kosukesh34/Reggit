<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_admin_ui_settings', function (Blueprint $table) {
            $table->string('navigation_header_image_url')->nullable()->after('frontend_logo_url');
            $table->string('home_menu_layout', 32)->default('grid_3x2')->after('navigation_header_image_url');
        });
    }

    public function down(): void
    {
        Schema::table('project_admin_ui_settings', function (Blueprint $table) {
            $table->dropColumn([
                'navigation_header_image_url',
                'home_menu_layout',
            ]);
        });
    }
};
