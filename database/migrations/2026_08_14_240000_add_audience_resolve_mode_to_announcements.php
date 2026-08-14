<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            if (! Schema::hasColumn('announcements', 'audience_resolve_mode')) {
                $table->string('audience_resolve_mode', 32)->default('snapshot')->after('target_member_ids')
                    ->comment('snapshot=作成時の対象者で固定, at_send=配信時に条件再評価');
            }
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            if (Schema::hasColumn('announcements', 'audience_resolve_mode')) {
                $table->dropColumn('audience_resolve_mode');
            }
        });
    }
};
