<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * アプリで参照する shop_settings のキーを、未登録の場合のみデフォルトで登録する。
 * 既存環境では値は変更せず、新規環境・キー不足時のみ拡張に備える。
 */
return new class extends Migration
{
    private function ensureKey(string $key, $value, string $type): void
    {
        if (DB::table('shop_settings')->where('key', $key)->exists()) {
            return;
        }
        DB::table('shop_settings')->insert([
            'key' => $key,
            'value' => is_array($value) ? json_encode($value) : (string) $value,
            'type' => $type,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function up(): void
    {
        $this->ensureKey('closed_dates', json_encode([]), 'json');
        $this->ensureKey('reservation_capacity_per_slot', '20', 'integer');
        $this->ensureKey('line_primary_color', '#000000', 'string');
        $this->ensureKey('line_primary_dark', '#333333', 'string');
        $this->ensureKey('line_success_color', '#000000', 'string');
        $this->ensureKey('line_danger_color', '#dc3545', 'string');
    }

    public function down(): void
    {
        $keys = [
            'closed_dates',
            'reservation_capacity_per_slot',
            'line_primary_color',
            'line_primary_dark',
            'line_success_color',
            'line_danger_color',
        ];
        DB::table('shop_settings')->whereIn('key', $keys)->delete();
    }
};
