<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupon_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->string('delivery_type', 40);
            $table->string('status', 20)->default('scheduled');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->json('target_member_ids')->nullable();
            $table->json('segment_criteria')->nullable();
            $table->unsignedInteger('issued_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['scheduled_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_deliveries');
    }
};
