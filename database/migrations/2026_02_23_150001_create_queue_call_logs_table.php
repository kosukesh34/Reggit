<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_call_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->index();
            $table->unsignedBigInteger('queue_entry_id')->unique();
            $table->unsignedInteger('queue_number')->nullable();
            $table->string('line_user_id')->nullable();
            $table->timestamp('called_at');
            $table->string('push_status', 32)->default('pending');
            $table->string('error_message')->nullable();
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('queue_entry_id')->references('id')->on('queue_entries')->onDelete('cascade');
            $table->index(['project_id', 'called_at'], 'idx_queue_call_logs_project_called_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_call_logs');
    }
};

