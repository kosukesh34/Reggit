<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table): void {
            $table->boolean('device_once_enabled')->default(false)->after('is_active');
        });

        Schema::create('coupon_device_issues', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('device_identifier', 255);
            $table->timestamp('issued_at');
            $table->timestamps();
            $table->unique(['project_id', 'coupon_id', 'device_identifier'], 'coupon_device_once_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_device_issues');
        Schema::table('coupons', function (Blueprint $table): void {
            $table->dropColumn('device_once_enabled');
        });
    }
};
