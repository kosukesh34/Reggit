<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 1億ユーザー規模を想定した複合インデックスの追加。
 * 既存のインデックスと重複しないよう、存在チェックしてから追加する。
 */
return new class extends Migration
{
    public function up(): void
    {
        // users
        $this->addIndexSafely('users', ['project_id', 'created_at'], 'idx_users_project_created');

        // members
        $this->addIndexSafely('members', ['project_id', 'status', 'rank'], 'idx_members_project_status_rank');
        $this->addIndexSafely('members', ['project_id', 'user_id'], 'idx_members_project_user');

        // orders
        $this->addIndexSafely('orders', ['project_id', 'status', 'created_at'], 'idx_orders_project_status_created');
        $this->addIndexSafely('orders', ['project_id', 'user_id', 'created_at'], 'idx_orders_project_user_created');

        // order_items
        $this->addIndexSafely('order_items', ['order_id', 'product_id'], 'idx_order_items_order_product');

        // products
        $this->addIndexSafely('products', ['project_id', 'category', 'is_available'], 'idx_products_project_cat_avail');

        // reservations
        $this->addIndexSafely('reservations', ['project_id', 'status', 'reserved_at'], 'idx_reservations_project_status_date');

        // point_transactions
        $this->addIndexSafely('point_transactions', ['member_id', 'type', 'created_at'], 'idx_pt_member_type_created');
        $this->addIndexSafely('point_transactions', ['project_id', 'created_at'], 'idx_pt_project_created');

        // queue_entries
        $this->addIndexSafely('queue_entries', ['project_id', 'status', 'created_at'], 'idx_queue_project_status_created');

        // member_coupons
        $this->addIndexSafely('member_coupons', ['project_id', 'member_id', 'status'], 'idx_mc_project_member_status');

        // admin_users
        $this->addIndexSafely('admin_users', ['role', 'project_id'], 'idx_admin_users_role_project');

        // announcements
        $this->addIndexSafely('announcements', ['project_id', 'published_at', 'expires_at'], 'idx_announce_project_pub_exp');

        // coupon_redemptions
        $this->addIndexSafely('coupon_redemptions', ['project_id', 'coupon_id', 'used_at'], 'idx_cr_project_coupon_used');

        // stamp_cards
        $this->addIndexSafely('stamp_cards', ['project_id', 'is_active'], 'idx_stamps_project_active');

        // member_stamps
        $this->addIndexSafely('member_stamps', ['project_id', 'member_id'], 'idx_ms_project_member');
    }

    public function down(): void
    {
        $indexes = [
            'users' => ['idx_users_project_created'],
            'members' => ['idx_members_project_status_rank', 'idx_members_project_user'],
            'orders' => ['idx_orders_project_status_created', 'idx_orders_project_user_created'],
            'order_items' => ['idx_order_items_order_product'],
            'products' => ['idx_products_project_cat_avail'],
            'reservations' => ['idx_reservations_project_status_date'],
            'point_transactions' => ['idx_pt_member_type_created', 'idx_pt_project_created'],
            'queue_entries' => ['idx_queue_project_status_created'],
            'member_coupons' => ['idx_mc_project_member_status'],
            'admin_users' => ['idx_admin_users_role_project'],
            'announcements' => ['idx_announce_project_pub_exp'],
            'coupon_redemptions' => ['idx_cr_project_coupon_used'],
            'stamp_cards' => ['idx_stamps_project_active'],
            'member_stamps' => ['idx_ms_project_member'],
        ];

        foreach ($indexes as $table => $idxNames) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $t) use ($idxNames) {
                foreach ($idxNames as $name) {
                    try {
                        $t->dropIndex($name);
                    } catch (\Throwable $e) {
                        // インデックスが存在しない場合はスキップ
                    }
                }
            });
        }
    }

    private function addIndexSafely(string $table, array $columns, string $indexName): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $t) use ($columns, $indexName) {
                $t->index($columns, $indexName);
            });
        } catch (\Throwable $e) {
            // インデックスが既に存在する場合はスキップ
        }
    }
};
