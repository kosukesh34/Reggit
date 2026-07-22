<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_profile_smaregi_settings', function (Blueprint $table) {
            $table->string('smaregi_store_id', 64)->nullable()->after('smaregi_client_secret');
            $table->string('smaregi_terminal_id', 64)->nullable()->after('smaregi_store_id');
        });
    }

    public function down(): void
    {
        Schema::table('project_profile_smaregi_settings', function (Blueprint $table) {
            $table->dropColumn(['smaregi_store_id', 'smaregi_terminal_id']);
        });
    }
};
