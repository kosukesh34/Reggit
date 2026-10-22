<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_admin_ui_settings', function (Blueprint $table) {
            $table->string('shop_hero_headline')->nullable()->after('frontend_header_text');
            $table->string('shop_hero_subcopy', 500)->nullable()->after('shop_hero_headline');
            $table->string('shop_hero_image_url', 2048)->nullable()->after('shop_hero_subcopy');
            $table->json('shop_featured_product_ids')->nullable()->after('shop_hero_image_url');
        });
    }

    public function down(): void
    {
        Schema::table('project_admin_ui_settings', function (Blueprint $table) {
            $table->dropColumn([
                'shop_hero_headline',
                'shop_hero_subcopy',
                'shop_hero_image_url',
                'shop_featured_product_ids',
            ]);
        });
    }
};
