<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            Schema::getConnection()->statement(
                "ALTER TABLE `point_transactions` MODIFY COLUMN `type` ENUM('earned','used','expired','refunded','reversed') NOT NULL DEFAULT 'earned'"
            );
        } else {
            Schema::table('point_transactions', function (Blueprint $table) {
                $table->string('type', 20)->default('earned')->change();
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            Schema::getConnection()->statement(
                "ALTER TABLE `point_transactions` MODIFY COLUMN `type` ENUM('earned','used','expired','refunded') NOT NULL DEFAULT 'earned'"
            );
        }
    }
};
