<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_registration_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('member_number', 20);
            $table->string('contract_id');
            $table->uuid('registration_key')->unique();
            $table->timestamps();
            $table->unique(['project_id', 'member_number'], 'registration_attempt_project_number_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_registration_attempts');
    }
};
