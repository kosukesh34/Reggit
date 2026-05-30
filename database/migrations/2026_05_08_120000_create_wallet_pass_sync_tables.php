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
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('member_id');
            $table->string('pass_type_identifier', 255);
            $table->string('serial_number', 255);
            $table->string('device_library_identifier', 255);
            $table->string('push_token', 512);
            $table->timestamps();

            $table->unique(
                ['pass_type_identifier', 'serial_number', 'device_library_identifier'],
                'wallet_pass_registrations_unique'
            );
            $table->index(['member_id', 'pass_type_identifier'], 'wallet_pass_registrations_member_idx');
            $table->index(['device_library_identifier', 'pass_type_identifier'], 'wallet_pass_registrations_device_idx');
        });

        Schema::create('wallet_pass_updates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('member_id');
            $table->string('pass_type_identifier', 255);
            $table->string('serial_number', 255);
            $table->timestamp('updated_at')->useCurrent();

            $table->unique(['member_id', 'pass_type_identifier'], 'wallet_pass_updates_member_unique');
            $table->index(['pass_type_identifier', 'updated_at'], 'wallet_pass_updates_pass_type_updated_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_pass_updates');
        Schema::dropIfExists('wallet_pass_registrations');
    }
};
