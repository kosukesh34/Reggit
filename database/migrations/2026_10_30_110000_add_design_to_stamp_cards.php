<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stamp_cards', function (Blueprint $table) {
            // 会員アプリのスタンプ画面の見せ方（カード画像の置き方）。並べ方の layout_style とは別
            $table->string('design', 20)->default('standard')->after('layout_style');
        });
    }

    public function down(): void
    {
        Schema::table('stamp_cards', fn (Blueprint $table) => $table->dropColumn('design'));
    }
};
