<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_delivery_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('after_registration_delivery_id')->constrained(indexName: 'scheduled_delivery_attempt_delivery_fk')->cascadeOnDelete();
            $table->string('occurrence_key', 64);
            $table->dateTime('scheduled_at');
            $table->json('delivery_snapshot');
            $table->json('target_member_ids')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->dateTime('next_attempt_at');
            $table->dateTime('failed_at')->nullable();
            $table->timestamps();
            $table->unique(['after_registration_delivery_id', 'occurrence_key'], 'scheduled_delivery_attempt_occurrence');
            $table->index(['failed_at', 'next_attempt_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_delivery_attempts');
    }
};
