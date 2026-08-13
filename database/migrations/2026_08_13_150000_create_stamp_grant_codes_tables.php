<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stamp_grant_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->index();
            $table->foreignId('stamp_card_id')->constrained()->cascadeOnDelete();
            $table->string('token', 12)->unique();
            $table->unsignedTinyInteger('amount');
            $table->string('usage_limit', 16);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('stamp_grant_redemptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->index();
            $table->foreignId('stamp_grant_code_id')->constrained('stamp_grant_codes')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->unique(['stamp_grant_code_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stamp_grant_redemptions');
        Schema::dropIfExists('stamp_grant_codes');
    }
};
