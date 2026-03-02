<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // staff ロールを ENUM に追加
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE admin_users MODIFY COLUMN role ENUM('system_admin', 'project_admin', 'admin', 'staff') NOT NULL DEFAULT 'staff'");
        }

        // created_by_id: 誰がこのアカウントを作成したか追跡
        if (! Schema::hasColumn('admin_users', 'created_by_id')) {
            Schema::table('admin_users', function (Blueprint $table) {
                $table->foreignId('created_by_id')->nullable()->after('project_id')
                    ->constrained('admin_users')->nullOnDelete()
                    ->comment('作成者の admin_user_id');
            });
        }
    }

    public function down(): void
    {
        // staff → admin に戻す
        DB::table('admin_users')->where('role', 'staff')->update(['role' => 'admin']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE admin_users MODIFY COLUMN role ENUM('system_admin', 'project_admin', 'admin') NOT NULL DEFAULT 'admin'");
        }

        if (Schema::hasColumn('admin_users', 'created_by_id')) {
            Schema::table('admin_users', function (Blueprint $table) {
                $table->dropForeign(['created_by_id']);
                $table->dropColumn('created_by_id');
            });
        }
    }
};
