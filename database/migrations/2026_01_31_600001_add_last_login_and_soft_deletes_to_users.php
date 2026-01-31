<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * users: 最終ログイン日時・論理削除を追加。
 * モバイルアプリ対応時も「誰がいつ使ったか」を追いやすくする。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable()->after('remember_token')->comment('最終ログイン日時（LINE/アプリ問わず更新）');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_login_at', 'deleted_at']);
        });
    }
};
