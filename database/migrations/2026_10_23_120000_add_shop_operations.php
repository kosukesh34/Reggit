<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_business_settings', function (Blueprint $table): void {
            $table->json('shop_legal_notice')->nullable()->after('shop_shipping_fees_by_prefecture')->comment('特定商取引法に基づく表記');
            $table->unsignedInteger('shop_free_shipping_threshold')->nullable()->after('shop_legal_notice')->comment('この金額以上で送料無料。NULLは無効');
            $table->boolean('shop_delivery_date_enabled')->default(false)->after('shop_free_shipping_threshold');
            $table->unsignedTinyInteger('shop_delivery_lead_days')->default(3)->after('shop_delivery_date_enabled')->comment('最短お届け日（注文日からの日数）');
            $table->unsignedTinyInteger('shop_delivery_range_days')->default(14)->after('shop_delivery_lead_days')->comment('指定できる期間（日数）');
            $table->json('shop_delivery_time_slots')->nullable()->after('shop_delivery_range_days');
            $table->string('shop_invoice_registration_number', 14)->nullable()->after('shop_delivery_time_slots')->comment('適格請求書発行事業者の登録番号');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->unsignedTinyInteger('tax_rate')->default(10)->after('price')->comment('消費税率（8: 軽減税率, 10: 標準税率）');
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->unsignedTinyInteger('tax_rate')->default(10)->after('price');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->date('delivery_date')->nullable()->after('shipping_fee');
            $table->string('delivery_time_slot', 32)->nullable()->after('delivery_date');
            $table->string('shipping_carrier', 32)->nullable()->after('delivery_time_slot');
            $table->string('shipping_tracking_number', 64)->nullable()->after('shipping_carrier');
            $table->timestamp('refunded_at')->nullable()->after('shipping_tracking_number');
            $table->unsignedInteger('refund_amount')->nullable()->after('refunded_at');
            $table->string('stripe_refund_id')->nullable()->after('refund_amount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['delivery_date', 'delivery_time_slot', 'shipping_carrier', 'shipping_tracking_number', 'refunded_at', 'refund_amount', 'stripe_refund_id']);
        });
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn('tax_rate');
        });
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('tax_rate');
        });
        Schema::table('project_business_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'shop_legal_notice', 'shop_free_shipping_threshold', 'shop_delivery_date_enabled',
                'shop_delivery_lead_days', 'shop_delivery_range_days', 'shop_delivery_time_slots', 'shop_invoice_registration_number',
            ]);
        });
    }
};
