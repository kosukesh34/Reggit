<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const DEFAULT_PROJECT_ID = 1;

    public function up(): void
    {
        $tables = [
            'users',
            'members',
            'products',
            'orders',
            'order_items',
            'reservations',
            'point_transactions',
            'shop_settings',
            'stamp_cards',
            'member_stamps',
            'coupons',
            'coupon_redemptions',
            'announcements',
            'queue_entries',
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('project_id')->nullable()->after('id')->constrained('projects')->onDelete('cascade');
            });
        }

        foreach ($tables as $tableName) {
            DB::table($tableName)->update(['project_id' => self::DEFAULT_PROJECT_ID]);
        }

        $driver = Schema::getConnection()->getDriverName();
        foreach ($tables as $tableName) {
            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE `{$tableName}` MODIFY `project_id` BIGINT UNSIGNED NOT NULL");
            } else {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unsignedBigInteger('project_id')->nullable(false)->change();
                });
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_line_user_id_unique');
            $table->unique(['project_id', 'line_user_id']);
        });

        Schema::table('members', function (Blueprint $table) {
            $table->dropUnique('members_member_number_unique');
            $table->unique(['project_id', 'member_number']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique('orders_order_number_unique');
            $table->unique(['project_id', 'order_number']);
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropUnique('reservations_reservation_number_unique');
            $table->unique(['project_id', 'reservation_number']);
        });

        Schema::table('shop_settings', function (Blueprint $table) {
            $table->dropUnique('shop_settings_key_unique');
            $table->unique(['project_id', 'key']);
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropUnique('coupons_code_unique');
            $table->unique(['project_id', 'code']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->index('project_key');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['project_id', 'line_user_id']);
            $table->unique('line_user_id');
        });
        Schema::table('members', function (Blueprint $table) {
            $table->dropUnique(['project_id', 'member_number']);
            $table->unique('member_number');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['project_id', 'order_number']);
            $table->unique('order_number');
        });
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropUnique(['project_id', 'reservation_number']);
            $table->unique('reservation_number');
        });
        Schema::table('shop_settings', function (Blueprint $table) {
            $table->dropUnique(['project_id', 'key']);
            $table->unique('key');
        });
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropUnique(['project_id', 'code']);
            $table->unique('code');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['project_key']);
        });

        $tables = [
            'users', 'members', 'products', 'orders', 'order_items',
            'reservations', 'point_transactions', 'shop_settings',
            'stamp_cards', 'member_stamps', 'coupons', 'coupon_redemptions',
            'announcements', 'queue_entries',
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['project_id']);
            });
        }
    }
};
