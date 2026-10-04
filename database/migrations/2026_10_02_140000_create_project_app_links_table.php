<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_app_links', function (Blueprint $table) {
            $table->foreignId('project_id')->primary()->constrained('projects')->cascadeOnDelete();
            $table->string('download_url', 2048)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_app_links');
    }
};
