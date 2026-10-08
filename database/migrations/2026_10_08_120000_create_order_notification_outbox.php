<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_notification_outbox', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('order_id');
            $table->string('event', 32);
            $table->string('channel', 32);
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('available_at');
            $table->string('lease', 32)->nullable();
            $table->timestamp('leased_until')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique(['order_id', 'event', 'channel'], 'order_notification_once');
            $table->index(['delivered_at', 'available_at'], 'order_notification_pending');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_notification_outbox');
    }
};
