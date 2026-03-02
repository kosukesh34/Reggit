<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('coupons', 'issue_trigger')) {
            Schema::table('coupons', function (Blueprint $table) {
                $table->string('issue_trigger', 32)->default('manual')->after('is_active')
                    ->comment('manual=手動, first_registration=初回登録, stamp_completed=スタンプ達成');
            });
        }
        if (! Schema::hasColumn('coupons', 'stamp_card_id')) {
            Schema::table('coupons', function (Blueprint $table) {
                $table->foreignId('stamp_card_id')->nullable()->after('issue_trigger')
                    ->constrained('stamp_cards')->nullOnDelete()
                    ->comment('issue_trigger=stamp_completed のとき対象スタンプカード');
            });
        }
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropForeign(['stamp_card_id']);
            $table->dropColumn(['issue_trigger', 'stamp_card_id']);
        });
    }
};
