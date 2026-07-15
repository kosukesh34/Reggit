<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_line_liff_settings', function (Blueprint $table) {
            $table->json('liff_detail_layouts')->nullable()->after('liff_tab_layouts');
        });
    }

    public function down(): void
    {
        Schema::table('project_line_liff_settings', function (Blueprint $table) {
            $table->dropColumn('liff_detail_layouts');
        });
    }
};
