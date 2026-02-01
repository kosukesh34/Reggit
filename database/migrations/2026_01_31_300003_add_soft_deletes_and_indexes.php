<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 細かなDB定義: projects に論理削除、shop_settings に type インデックス。
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('projects', 'deleted_at')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (DB::getDriverName() === 'mysql') {
            $hasTypeIndex = DB::select("SHOW INDEX FROM shop_settings WHERE Column_name = 'type'");
            if (empty($hasTypeIndex)) {
                Schema::table('shop_settings', function (Blueprint $table) {
                    $table->index('type');
                });
            }
        } else {
            Schema::table('shop_settings', function (Blueprint $table) {
                $table->index('type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('projects', 'deleted_at')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('deleted_at');
            });
        }

        Schema::table('shop_settings', function (Blueprint $table) {
            $table->dropIndex(['type']);
        });
    }
};
