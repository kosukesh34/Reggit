<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admin_user_projects')) {
            return;
        }
        Schema::create('admin_user_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_user_id')->constrained('admin_users')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['admin_user_id', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_user_projects');
    }
};
