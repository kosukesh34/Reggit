<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_grant_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('token', 48)->unique();
            $table->unsignedInteger('points');
            $table->unsignedInteger('usage_quota');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
        Schema::create('point_grant_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('point_grant_code_id')->constrained('point_grant_codes')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['point_grant_code_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_grant_redemptions');
        Schema::dropIfExists('point_grant_codes');
    }
};
