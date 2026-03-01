<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stamp_cards', function (Blueprint $table) {
            $table->json('reward_milestones')->nullable()->after('reward_description')
                ->comment('到達スタンプ数ごとの特典一覧');
        });
    }

    public function down(): void
    {
        Schema::table('stamp_cards', function (Blueprint $table) {
            $table->dropColumn('reward_milestones');
        });
    }
};
