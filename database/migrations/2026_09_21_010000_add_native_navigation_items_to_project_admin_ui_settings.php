<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_admin_ui_settings', function (Blueprint $table) {
            $table->json('native_navigation_items')->nullable()->after('product_display_layout');
        });
    }

    public function down(): void
    {
        Schema::table('project_admin_ui_settings', function (Blueprint $table) {
            $table->dropColumn('native_navigation_items');
        });
    }
};
