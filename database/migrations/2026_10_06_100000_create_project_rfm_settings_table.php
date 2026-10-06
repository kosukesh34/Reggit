<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_rfm_settings', function (Blueprint $table) {
            $table->foreignId('project_id')->primary()->constrained('projects')->cascadeOnDelete();
            $table->unsignedInteger('recency_active_days')->default(30);
            $table->unsignedInteger('recency_sleeping_days')->default(90);
            $table->unsignedInteger('frequency_high')->default(5);
            $table->unsignedInteger('monetary_high')->default(10000);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_rfm_settings');
    }
};
