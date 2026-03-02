<?php

use App\Models\AdminUser;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admin_users')) {
            return;
        }

        DB::table('admin_users')
            ->where('role', 'system')
            ->update(['role' => AdminUser::ROLE_SYSTEM_ADMIN]);

        DB::table('admin_users')
            ->where('role', 'sys_admin')
            ->update(['role' => AdminUser::ROLE_SYSTEM_ADMIN]);

        DB::table('admin_users')
            ->where('role', 'system-admin')
            ->update(['role' => AdminUser::ROLE_SYSTEM_ADMIN]);

        DB::table('admin_users')
            ->where('role', 'store_admin')
            ->update(['role' => AdminUser::ROLE_ADMIN]);
    }

    public function down(): void
    {
        // no-op: legacy role valuesへ戻す運用は行わない
    }
};

