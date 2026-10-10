<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->boolean('smaregi_pickup_pending')->default(false);
            $table->dateTime('smaregi_pickup_retry_at')->nullable();
            $table->index(['smaregi_pickup_pending', 'smaregi_pickup_retry_at'], 'order_smaregi_pickup_retry');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('order_smaregi_pickup_retry');
            $table->dropColumn(['smaregi_pickup_pending', 'smaregi_pickup_retry_at']);
        });
    }
};
