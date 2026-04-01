<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_pass_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_pass_id')->constrained('wallet_passes')->cascadeOnDelete();
            $table->string('device_library_identifier', 255);
            $table->string('push_token', 255);
            $table->timestamps();

            $table->unique(['wallet_pass_id', 'device_library_identifier'], 'uq_wallet_pass_regs_pass_device');
            $table->index(['project_id', 'device_library_identifier'], 'idx_wallet_pass_regs_project_device');
            $table->index(['project_id', 'push_token'], 'idx_wallet_pass_regs_project_push_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_pass_registrations');
    }
};
