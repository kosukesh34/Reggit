<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_passes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->string('pass_type_identifier', 255);
            $table->string('serial_number', 255);
            $table->string('authentication_token', 128);
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'pass_type_identifier', 'serial_number'], 'uq_wallet_passes_project_type_serial');
            $table->unique('authentication_token');
            $table->index(['project_id', 'member_id'], 'idx_wallet_passes_project_member');
            $table->index(['pass_type_identifier', 'serial_number'], 'idx_wallet_passes_type_serial');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_passes');
    }
};
