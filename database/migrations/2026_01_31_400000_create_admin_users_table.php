<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admin_users')) {
            return;
        }
        Schema::create('admin_users', function (Blueprint $table) {
            $table->id();
            $table->string('login_id', 50)->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('name');
            $table->string('role', 30)->default('admin');
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_users');
    }
};
