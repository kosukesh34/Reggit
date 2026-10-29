<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('line_follow_events', function (Blueprint $table) {
            // LINE は同じイベントを再送することがあるため、これで1回にまとめる
            $table->string('webhook_event_id', 64)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('line_follow_events', fn (Blueprint $table) => $table->dropColumn('webhook_event_id'));
    }
};
