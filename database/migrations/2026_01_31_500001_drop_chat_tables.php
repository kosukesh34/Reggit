<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * チャット機能を使わないため、chat_messages / chat_rooms テーブルを削除する。
 * 既にマイグレーション済みのDBでテーブルが存在する場合に DROP する。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_rooms');
    }

    public function down(): void
    {
        // チャット機能は使わないため、down ではテーブルを再作成しない
    }
};
