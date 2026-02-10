<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stamp_cards', function (Blueprint $table) {
            if (!Schema::hasColumn('stamp_cards', 'image_url')) {
                $table->string('image_url', 500)->nullable()->after('reward_description');
            }
        });

        Schema::table('coupons', function (Blueprint $table) {
            if (!Schema::hasColumn('coupons', 'image_url')) {
                $table->string('image_url', 500)->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stamp_cards', function (Blueprint $table) {
            if (Schema::hasColumn('stamp_cards', 'image_url')) {
                $table->dropColumn('image_url');
            }
        });

        Schema::table('coupons', function (Blueprint $table) {
            if (Schema::hasColumn('coupons', 'image_url')) {
                $table->dropColumn('image_url');
            }
        });
    }
};
