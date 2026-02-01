<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * users の新カラム（last_login_at, deleted_at）と user_devices 全カラムに COMMENT を付与（MySQL）。
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasColumn('users', 'last_login_at')) {
            DB::statement("ALTER TABLE `users` MODIFY COLUMN `last_login_at` TIMESTAMP NULL COMMENT '最終ログイン日時（LINE/アプリ問わず更新）'");
        }
        if (Schema::hasColumn('users', 'deleted_at')) {
            DB::statement("ALTER TABLE `users` MODIFY COLUMN `deleted_at` TIMESTAMP NULL COMMENT '論理削除日時（NULL=有効）'");
        }

        if (!Schema::hasTable('user_devices')) {
            return;
        }

        $comments = [
            'id' => 'PK',
            'project_id' => 'FK → projects.id',
            'user_id' => 'FK → users.id',
            'platform' => 'ios / android 等',
            'device_identifier' => '端末一意ID（アプリ側で生成）',
            'push_token' => 'プッシュ通知用トークン',
            'os_version' => 'OSバージョン',
            'app_version' => 'アプリバージョン',
            'last_used_at' => '最終利用日時',
            'created_at' => '作成日時',
            'updated_at' => '更新日時',
        ];

        $cols = DB::select(
            "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_devices'"
        );
        foreach ($cols as $c) {
            $colName = $c->COLUMN_NAME;
            if (!isset($comments[$colName])) {
                continue;
            }
            $comment = addslashes($comments[$colName]);
            $type = $c->COLUMN_TYPE;
            $nullable = $c->IS_NULLABLE === 'YES' ? ' NULL' : ' NOT NULL';
            $default = '';
            if ($c->COLUMN_DEFAULT !== null && strpos($c->EXTRA ?? '', 'auto_increment') === false) {
                $d = $c->COLUMN_DEFAULT;
                $default = is_numeric($d) ? " DEFAULT {$d}" : " DEFAULT '" . addslashes((string)$d) . "'";
            }
            $extra = $c->EXTRA ? ' ' . $c->EXTRA : '';
            DB::statement("ALTER TABLE `user_devices` MODIFY COLUMN `{$colName}` {$type}{$nullable}{$default}{$extra} COMMENT '{$comment}'");
        }
    }

    public function down(): void
    {
        // COMMENT のみの変更のため down では何もしない
    }
};
