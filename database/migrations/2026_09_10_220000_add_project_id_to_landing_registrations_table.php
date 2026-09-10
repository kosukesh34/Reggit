<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_registrations', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('email')->constrained('projects')->nullOnDelete();
            $table->string('admin_login_id', 100)->nullable()->after('project_id');
        });
    }

    public function down(): void
    {
        Schema::table('landing_registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_id');
            $table->dropColumn('admin_login_id');
        });
    }
};
