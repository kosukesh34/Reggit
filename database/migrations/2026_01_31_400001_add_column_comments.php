<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 全カラムに COMMENT を付与する（MySQL）。
 * 各カラムの意味をDB上で明確にし、運用・拡張時の可読性を高める。
 * information_schema から現在のカラム定義を取得し、COMMENT のみ付与する。
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver !== 'mysql') {
            return;
        }

        $comments = $this->getComments();

        foreach ($comments as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            $cols = DB::select(
                "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
                [$table]
            );
            foreach ($cols as $c) {
                $colName = $c->COLUMN_NAME;
                if (!isset($columns[$colName])) {
                    continue;
                }
                $comment = addslashes($columns[$colName]);
                $type = $c->COLUMN_TYPE;
                $nullable = $c->IS_NULLABLE === 'YES' ? ' NULL' : ' NOT NULL';
                $default = '';
                if ($c->COLUMN_DEFAULT !== null && strpos($c->EXTRA ?? '', 'auto_increment') === false) {
                    $d = $c->COLUMN_DEFAULT;
                    $default = is_numeric($d) ? " DEFAULT {$d}" : " DEFAULT '" . addslashes((string)$d) . "'";
                }
                $extra = $c->EXTRA ? ' ' . $c->EXTRA : '';
                DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `{$colName}` {$type}{$nullable}{$default}{$extra} COMMENT '{$comment}'");
            }
        }
    }

    private function getComments(): array
    {
        return [
            'projects' => [
                'id' => 'PK。他テーブルの project_id がこれを参照する。',
                'project_key' => 'URL・API識別用の一意キー（英数字）。Ex: default, shop_tokyo',
                'name' => '店舗・プロジェクト名',
                'description' => '説明',
                'is_active' => '有効: 1, 無効: 0',
                'created_at' => '作成日時',
                'updated_at' => '更新日時',
                'deleted_at' => '論理削除日時（NULL=有効）',
            ],
            'users' => [
                'id' => 'PK',
                'project_id' => 'FK → projects.id。所属プロジェクト（店舗）',
                'line_user_id' => 'LINE ユーザーID。(project_id, line_user_id) で UNIQUE',
                'name' => '表示名',
                'email' => 'メールアドレス',
                'phone' => '電話番号',
                'profile_image_url' => 'プロフィール画像URL',
                'email_verified_at' => 'メール認証日時',
                'remember_token' => '認証用トークン',
                'created_at' => '作成日時',
                'updated_at' => '更新日時',
            ],
            'members' => [
                'id' => 'PK',
                'project_id' => 'FK → projects.id',
                'user_id' => 'FK → users.id',
                'member_number' => '会員番号。(project_id, member_number) で UNIQUE',
                'points' => '保有ポイント数',
                'status' => '会員状態。active: 有効, inactive: 無効',
                'rank' => 'ランクコード。Ex: bronze, silver, gold',
                'birthday' => '誕生日',
                'address' => '住所',
                'created_at' => '作成日時',
                'updated_at' => '更新日時',
            ],
            'products' => [
                'id' => 'PK',
                'project_id' => 'FK → projects.id',
                'name' => '商品名',
                'description' => '説明文',
                'price' => '単価',
                'image_url' => '画像URL',
                'category' => 'カテゴリ。food, drink, dessert, side, other',
                'is_available' => '販売可否。1: 可, 0: 不可',
                'stock' => '在庫数',
                'created_at' => '作成日時',
                'updated_at' => '更新日時',
            ],
            'orders' => [
                'id' => 'PK',
                'project_id' => 'FK → projects.id',
                'user_id' => 'FK → users.id',
                'order_number' => '注文番号。(project_id, order_number) で UNIQUE',
                'order_type' => '注文種別。mobile: モバイルオーダー, in_store: 店内注文',
                'total_amount' => '合計金額',
                'status' => 'ステータス。pending, confirmed, preparing, ready, completed, cancelled',
                'payment_method' => '支払方法。cash, card, line_pay, points, stripe',
                'notes' => '備考',
                'points_used' => '利用ポイント数',
                'points_earned' => '付与ポイント数',
                'created_at' => '作成日時',
                'updated_at' => '更新日時',
            ],
            'order_items' => [
                'id' => 'PK',
                'project_id' => 'FK → projects.id',
                'order_id' => 'FK → orders.id',
                'product_id' => 'FK → products.id',
                'quantity' => '数量',
                'price' => '単価（スナップショット）',
                'notes' => '備考（アレルギー等）',
                'created_at' => '作成日時',
                'updated_at' => '更新日時',
            ],
            'reservations' => [
                'id' => 'PK',
                'project_id' => 'FK → projects.id',
                'user_id' => 'FK → users.id',
                'reservation_number' => '予約番号。(project_id, reservation_number) で UNIQUE',
                'reserved_at' => '予約日時',
                'number_of_people' => '人数',
                'status' => '予約状態。pending, confirmed, cancelled, completed',
                'notes' => '備考',
                'created_at' => '作成日時',
                'updated_at' => '更新日時',
            ],
            'point_transactions' => [
                'id' => 'PK',
                'project_id' => 'FK → projects.id',
                'member_id' => 'FK → members.id',
                'type' => '種別。earned: 付与, used: 利用, expired: 失効, refunded: 返却',
                'points' => 'ポイント数（正: 付与, 負: 利用等）',
                'description' => '説明',
                'order_id' => 'FK → orders.id。紐づき注文（任意）',
                'expires_at' => '有効期限（付与時）',
                'created_at' => '作成日時',
                'updated_at' => '更新日時',
            ],
            'shop_settings' => [
                'id' => 'PK',
                'project_id' => 'FK → projects.id',
                'key' => '設定キー。(project_id, key) で UNIQUE。Ex: business_hours_start',
                'value' => '値（JSONの場合は文字列化）',
                'type' => '値の型。string, integer, boolean, json',
                'created_at' => '作成日時',
                'updated_at' => '更新日時',
            ],
            'stamp_cards' => [
                'id' => 'PK',
                'project_id' => 'FK → projects.id',
                'name' => 'スタンプカード名',
                'required_stamps' => '達成に必要なスタンプ数',
                'reward_description' => '特典説明',
                'is_active' => '有効: 1, 無効: 0',
                'created_at' => '作成日時',
                'updated_at' => '更新日時',
            ],
            'member_stamps' => [
                'id' => 'PK',
                'project_id' => 'FK → projects.id',
                'member_id' => 'FK → members.id',
                'stamp_card_id' => 'FK → stamp_cards.id',
                'current_stamps' => '現在のスタンプ数',
                'completed_at' => '達成日時',
                'created_at' => '作成日時',
                'updated_at' => '更新日時',
            ],
            'coupons' => [
                'id' => 'PK',
                'project_id' => 'FK → projects.id',
                'code' => 'クーポンコード。(project_id, code) で UNIQUE',
                'name' => '表示名',
                'type' => '種別。percent: 率引, fixed: 固定額引',
                'value' => '割引率(%) または 固定額',
                'min_order_amount' => '最低注文金額',
                'usage_limit' => '総利用回数上限（NULL=無制限）',
                'used_count' => '利用回数',
                'start_at' => '利用開始日時',
                'end_at' => '利用終了日時',
                'is_active' => '有効: 1, 無効: 0',
                'created_at' => '作成日時',
                'updated_at' => '更新日時',
            ],
            'coupon_redemptions' => [
                'id' => 'PK',
                'project_id' => 'FK → projects.id',
                'coupon_id' => 'FK → coupons.id',
                'member_id' => 'FK → members.id',
                'order_id' => 'FK → orders.id。利用した注文（任意）',
                'used_at' => '利用日時',
                'created_at' => '作成日時',
                'updated_at' => '更新日時',
            ],
            'announcements' => [
                'id' => 'PK',
                'project_id' => 'FK → projects.id',
                'title' => 'タイトル',
                'body' => '本文',
                'published_at' => '公開日時',
                'expires_at' => '掲載終了日時',
                'is_pinned' => '固定表示。1: する, 0: しない',
                'created_at' => '作成日時',
                'updated_at' => '更新日時',
            ],
            'queue_entries' => [
                'id' => 'PK',
                'project_id' => 'FK → projects.id',
                'member_id' => 'FK → members.id。会員でない場合は NULL',
                'guest_name' => 'ゲスト名',
                'guest_phone' => '電話番号',
                'party_size' => '人数',
                'status' => '状態。waiting, called, entered, cancelled 等',
                'queue_number' => '待ち番号',
                'called_at' => '呼出日時',
                'entered_at' => '入店日時',
                'created_at' => '作成日時',
                'updated_at' => '更新日時',
            ],
        ];
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver !== 'mysql') {
            return;
        }
        $tables = ['projects', 'users', 'members', 'products', 'orders', 'order_items', 'reservations', 'point_transactions', 'shop_settings', 'stamp_cards', 'member_stamps', 'coupons', 'coupon_redemptions', 'announcements', 'queue_entries'];
        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            $cols = DB::select("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?", [$table]);
            foreach ($cols as $c) {
                $name = $c->COLUMN_NAME;
                $type = $c->COLUMN_TYPE;
                $nullable = $c->IS_NULLABLE === 'YES' ? ' NULL' : ' NOT NULL';
                $default = '';
                if ($c->COLUMN_DEFAULT !== null && strpos($c->EXTRA ?? '', 'auto_increment') === false) {
                    $d = $c->COLUMN_DEFAULT;
                    $default = is_numeric($d) ? " DEFAULT {$d}" : " DEFAULT '" . addslashes((string)$d) . "'";
                }
                $extra = $c->EXTRA ? ' ' . $c->EXTRA : '';
                DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `{$name}` {$type}{$nullable}{$default}{$extra} COMMENT ''");
            }
        }
    }
};
