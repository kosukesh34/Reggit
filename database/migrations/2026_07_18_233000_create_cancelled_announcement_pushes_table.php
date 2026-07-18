<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cancelled_announcement_pushes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('announcement_id');
            $table->string('title')->nullable();
            $table->timestamp('cancelled_at');
            $table->timestamps();

            $table->unique(['project_id', 'announcement_id'], 'cancelled_announcement_pushes_unique');
            $table->index(['project_id', 'cancelled_at'], 'cancelled_announcement_pushes_project_cancelled_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cancelled_announcement_pushes');
    }
};
