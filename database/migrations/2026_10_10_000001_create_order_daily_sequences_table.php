<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_daily_sequences', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('last_sequence')->default(0);
            $table->primary(['project_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_daily_sequences');
    }
};
