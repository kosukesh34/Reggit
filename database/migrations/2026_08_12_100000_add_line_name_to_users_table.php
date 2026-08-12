<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * users.line_name: LINE 表示名。エンドユーザー画面には出さず、プロフィール名（name）と分離する。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('line_name')->nullable()->after('name')->comment('LINE表示名（エンドユーザー画面には出さない）');
        });

        DB::table('users')
            ->whereNotNull('line_user_id')
            ->where('line_user_id', '!=', '')
            ->where(function ($query): void {
                $query->whereNull('line_name')->orWhere('line_name', '');
            })
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->whereNotIn('name', ['LINE User', 'Guest User'])
            ->update(['line_name' => DB::raw('name')]);

        DB::table('users')
            ->whereIn('name', ['LINE User', 'Guest User'])
            ->update(['name' => null]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('line_name');
        });
    }
};
