<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stamp_cards', function (Blueprint $table) {
            $table->string('layout_style', 30)->default('grid')->after('image_url')
                ->comment('UIパターン: grid, row, circle, minimal, card');
            $table->unsignedInteger('valid_days')->nullable()->after('layout_style')
                ->comment('初回スタンプから完了までの有効日数（null=無制限）');
            $table->date('valid_until')->nullable()->after('valid_days')
                ->comment('キャンペーン終了日（この日以降は新規利用不可）');
        });

        Schema::table('member_stamps', function (Blueprint $table) {
            $table->timestamp('valid_until')->nullable()->after('completed_at')
                ->comment('このカードの有効期限（valid_days 設定時のみ）');
        });
    }

    public function down(): void
    {
        Schema::table('stamp_cards', function (Blueprint $table) {
            $table->dropColumn(['layout_style', 'valid_days', 'valid_until']);
        });
        Schema::table('member_stamps', function (Blueprint $table) {
            $table->dropColumn('valid_until');
        });
    }
};
