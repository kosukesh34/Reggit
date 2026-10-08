<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('coupons')
            ->where('issue_trigger', 'winback')
            ->update(['issue_trigger' => 'manual']);
    }

    public function down(): void
    {
        // 自動発行の設定を意図せず復元しない。
    }
};
