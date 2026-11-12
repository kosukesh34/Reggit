<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** ネイティブアプリ・LIFF を最後に起動した日時。配信条件「アプリの起動」で使う */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_app_launched_at')->nullable()->after('last_login_at')->comment('最終アプリ起動日時');
        });

        // 既存会員は端末の最終利用日時を起動日時の初期値にする
        DB::statement(<<<'SQL'
            UPDATE users
            INNER JOIN (
                SELECT project_id, user_id, MAX(last_used_at) AS launched_at
                FROM user_devices
                WHERE last_used_at IS NOT NULL
                GROUP BY project_id, user_id
            ) AS devices ON devices.user_id = users.id AND devices.project_id = users.project_id
            SET users.last_app_launched_at = devices.launched_at
            SQL);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_app_launched_at');
        });
    }
};
