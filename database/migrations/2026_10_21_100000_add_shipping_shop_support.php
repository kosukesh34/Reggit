<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('shipping_name', 100)->nullable()->after('notes');
            $table->string('shipping_phone', 30)->nullable()->after('shipping_name');
            $table->string('shipping_postal_code', 16)->nullable()->after('shipping_phone');
            $table->string('shipping_address', 500)->nullable()->after('shipping_postal_code');
            $table->unsignedInteger('shipping_fee')->default(0)->after('shipping_address');
        });

        Schema::table('project_feature_flags', function (Blueprint $table): void {
            $table->boolean('feature_shop_enabled')->default(false)->after('feature_gallery_enabled');
        });

        Schema::table('project_business_settings', function (Blueprint $table): void {
            $table->unsignedInteger('shop_shipping_fee')->default(500)->after('queue_channel_native');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'shipping_name',
                'shipping_phone',
                'shipping_postal_code',
                'shipping_address',
                'shipping_fee',
            ]);
        });

        Schema::table('project_feature_flags', function (Blueprint $table): void {
            $table->dropColumn('feature_shop_enabled');
        });

        Schema::table('project_business_settings', function (Blueprint $table): void {
            $table->dropColumn('shop_shipping_fee');
        });
    }
};
