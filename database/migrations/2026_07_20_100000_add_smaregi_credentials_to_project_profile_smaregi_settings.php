<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_profile_smaregi_settings', function (Blueprint $table) {
            $table->string('smaregi_client_id')->nullable()->after('smaregi_contract_id');
            $table->text('smaregi_client_secret')->nullable()->after('smaregi_client_id');
        });
    }

    public function down(): void
    {
        Schema::table('project_profile_smaregi_settings', function (Blueprint $table) {
            $table->dropColumn(['smaregi_client_id', 'smaregi_client_secret']);
        });
    }
};
