<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promo_popups', function (Blueprint $table): void {
            $table->boolean('hide_when_answered')->default(true)->after('frequency')->comment('リンク先のアンケートに回答した会員には出さない');
        });

        // 会員は LINE とアプリ・端末をまたいで表示回数を数える
        Schema::create('promo_popup_member_views', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('promo_popup_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->timestamp('seen_at')->comment('最後に表示した日時');
            $table->timestamps();
            $table->unique(['promo_popup_id', 'member_id']);
            $table->index('member_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_popup_member_views');
        Schema::table('promo_popups', function (Blueprint $table): void {
            $table->dropColumn('hide_when_answered');
        });
    }
};
