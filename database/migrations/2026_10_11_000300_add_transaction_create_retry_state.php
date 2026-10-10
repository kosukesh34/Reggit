<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->boolean('smaregi_create_pending')->default(false);
            $table->dateTime('smaregi_create_retry_at')->nullable();
            $table->dateTime('smaregi_create_leased_until')->nullable();
            $table->index(['smaregi_create_pending', 'smaregi_create_retry_at'], 'order_smaregi_create_retry');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('order_smaregi_create_retry');
            $table->dropColumn(['smaregi_create_pending', 'smaregi_create_retry_at', 'smaregi_create_leased_until']);
        });
    }
};
