<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_business_settings', function (Blueprint $table) {
            $table->json('shop_shipping_fees_by_prefecture')->nullable()->after('shop_shipping_fee');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_prefecture', 16)->nullable()->after('shipping_postal_code');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('shipping_prefecture');
        });

        Schema::table('project_business_settings', function (Blueprint $table) {
            $table->dropColumn('shop_shipping_fees_by_prefecture');
        });
    }
};
