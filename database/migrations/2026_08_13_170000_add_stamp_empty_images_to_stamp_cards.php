<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stamp_cards', function (Blueprint $table) {
            $table->json('stamp_empty_images')->nullable()->after('stamp_images')
                ->comment('押す前の各スタンプ位置ごとの画像URL JSON {"1":"url",...}');
        });
    }

    public function down(): void
    {
        Schema::table('stamp_cards', function (Blueprint $table) {
            $table->dropColumn('stamp_empty_images');
        });
    }
};
