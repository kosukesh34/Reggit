<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->string('coupon_name_snapshot', 255)->nullable();
            $table->enum('status', ['unused', 'used'])->default('unused');
            $table->timestamp('issued_at');
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('member_coupons', function (Blueprint $table) {
            $table->index(['member_id', 'status']);
            $table->index('issued_at');
        });

        if (Schema::hasTable('coupon_redemptions')) {
            Schema::table('coupon_redemptions', function (Blueprint $table) {
                $table->foreignId('member_coupon_id')->nullable()->after('member_id')->constrained('member_coupons')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('coupon_redemptions') && Schema::hasColumn('coupon_redemptions', 'member_coupon_id')) {
            Schema::table('coupon_redemptions', function (Blueprint $table) {
                $table->dropForeign(['member_coupon_id']);
            });
        }
        Schema::dropIfExists('member_coupons');
    }
};
