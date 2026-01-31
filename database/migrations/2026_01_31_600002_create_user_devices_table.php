<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 端末情報用テーブル（モバイルアプリ対応用）。
 * 1ユーザーが複数端末を持てる。プッシュ通知・アプリ版管理に利用。
 * LINE のみの運用時は未使用でよい。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('platform', 20)->comment('ios / android 等');
            $table->string('device_identifier', 255)->nullable()->comment('端末一意ID（アプリ側で生成）');
            $table->string('push_token', 500)->nullable()->comment('プッシュ通知用トークン');
            $table->string('os_version', 50)->nullable()->comment('OSバージョン');
            $table->string('app_version', 50)->nullable()->comment('アプリバージョン');
            $table->timestamp('last_used_at')->nullable()->comment('最終利用日時');
            $table->timestamps();

            $table->index(['project_id', 'user_id']);
            $table->index('device_identifier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
