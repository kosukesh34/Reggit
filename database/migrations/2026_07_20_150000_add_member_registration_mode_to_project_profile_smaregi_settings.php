<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_profile_smaregi_settings', function (Blueprint $table) {
            $table->string('member_registration_mode', 32)->default('optional')->after('profile_field_settings');
        });
    }

    public function down(): void
    {
        Schema::table('project_profile_smaregi_settings', function (Blueprint $table) {
            $table->dropColumn('member_registration_mode');
        });
    }
};
