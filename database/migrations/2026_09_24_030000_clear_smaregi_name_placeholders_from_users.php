<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * スマレジ会員登録時の必須対策プレースホルダ（氏名「—」・カナ「ー」）が逆同期で users に入ったものを未入力に戻す。
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->whereIn('name', ['— —', '—'])->update(['name' => null]);
        DB::table('users')->whereIn('kana', ['ー ー', 'ー'])->update(['kana' => null]);
    }

    public function down(): void {}
};
