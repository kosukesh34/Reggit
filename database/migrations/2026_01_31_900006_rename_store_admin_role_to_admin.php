<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('admin_users')->where('role', 'store_admin')->update(['role' => 'admin']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE admin_users MODIFY COLUMN role ENUM('system_admin', 'project_admin', 'admin') NOT NULL DEFAULT 'admin'");
        }
    }

    public function down(): void
    {
        DB::table('admin_users')->where('role', 'admin')->update(['role' => 'store_admin']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE admin_users MODIFY COLUMN role ENUM('system_admin', 'project_admin', 'store_admin') NOT NULL DEFAULT 'store_admin'");
        }
    }
};
