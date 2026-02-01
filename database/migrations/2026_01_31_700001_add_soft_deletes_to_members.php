<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('members', 'deleted_at')) {
            return;
        }

        Schema::table('members', function (Blueprint $table) {
            $table->softDeletes();
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            Schema::getConnection()->statement(
                "ALTER TABLE `members` MODIFY COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT '論理削除日時（NULL=有効）'"
            );
        }
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
