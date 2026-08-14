<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            if (! Schema::hasColumn('coupons', 'apply_condition_division')) {
                $table->string('apply_condition_division', 1)->default('0')->after('min_order_amount')
                    ->comment('スマレジ適用条件区分 0=全適用 1=対象商品含む場合のみ');
            }
            if (! Schema::hasColumn('coupons', 'max_order_amount')) {
                $table->decimal('max_order_amount', 12, 2)->nullable()->after('apply_condition_division')
                    ->comment('スマレジ最高対象金額');
            }
            if (! Schema::hasColumn('coupons', 'combine_division')) {
                $table->string('combine_division', 1)->default('3')->after('max_order_amount')
                    ->comment('スマレジ併用区分 1=不可 2=一部可 3=可');
            }
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            foreach (['apply_condition_division', 'max_order_amount', 'combine_division'] as $column) {
                if (Schema::hasColumn('coupons', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
