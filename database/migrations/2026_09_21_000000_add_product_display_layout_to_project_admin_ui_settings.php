<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_admin_ui_settings', function (Blueprint $table) {
            $table->string('product_display_layout', 32)
                ->default('list_with_image')
                ->after('home_menu_layout');
        });
    }

    public function down(): void
    {
        Schema::table('project_admin_ui_settings', function (Blueprint $table) {
            $table->dropColumn('product_display_layout');
        });
    }
};
