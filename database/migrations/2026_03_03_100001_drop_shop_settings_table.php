<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * 正規化テーブル project_settings への移行完了後、旧 EAV テーブル shop_settings を削除する。
 * 本マイグレーションは 2026_03_03_100000_create_project_settings_table の実行・動作確認後に実行すること。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('shop_settings');
    }

    public function down(): void
    {
        // 復元は行わない（project_settings から再構築する運用とする）
    }
};
