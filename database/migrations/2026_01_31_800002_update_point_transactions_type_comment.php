<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'mysql') {
            return;
        }
        Schema::getConnection()->statement(
            "ALTER TABLE `point_transactions` MODIFY COLUMN `type` ENUM('earned','used','expired','refunded','reversed') NOT NULL DEFAULT 'earned' COMMENT '種別。earned: 付与, used: 利用, expired: 失効, refunded: 返却, reversed: 取り消し（キャンセル等）'"
        );
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'mysql') {
            return;
        }
        Schema::getConnection()->statement(
            "ALTER TABLE `point_transactions` MODIFY COLUMN `type` ENUM('earned','used','expired','refunded','reversed') NOT NULL DEFAULT 'earned' COMMENT '種別。earned: 付与, used: 利用, expired: 失効, refunded: 返却'"
        );
    }
};
