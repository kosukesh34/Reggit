<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->foreignId('delivery_coupon_id')->nullable()->after('segment_criteria')
                ->constrained('coupons')->nullOnDelete()
                ->comment('配信と同時に配布するクーポン（任意）');
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropForeign(['delivery_coupon_id']);
        });
    }
};
