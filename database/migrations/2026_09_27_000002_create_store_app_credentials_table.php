<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_app_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admin_user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('personal_access_token_id')->nullable()->index();
            $table->string('app', 20);
            $table->string('enrollment_token_hash', 64)->nullable()->index();
            $table->string('device_key_hash', 64)->nullable();
            $table->string('device_name', 100)->nullable();
            $table->timestamp('enrollment_created_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'app']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_app_credentials');
    }
};
