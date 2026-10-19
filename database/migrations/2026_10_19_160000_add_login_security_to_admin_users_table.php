<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_users', function (Blueprint $table) {
            if (! Schema::hasColumn('admin_users', 'login_allowed_ips')) {
                $table->text('login_allowed_ips')->nullable()->after('remember_token');
            }
            if (! Schema::hasColumn('admin_users', 'login_notify_by_email')) {
                $table->boolean('login_notify_by_email')->default(false)->after('login_allowed_ips');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admin_users', function (Blueprint $table) {
            if (Schema::hasColumn('admin_users', 'login_notify_by_email')) {
                $table->dropColumn('login_notify_by_email');
            }
            if (Schema::hasColumn('admin_users', 'login_allowed_ips')) {
                $table->dropColumn('login_allowed_ips');
            }
        });
    }
};
