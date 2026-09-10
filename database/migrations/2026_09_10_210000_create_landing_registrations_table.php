<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('email', 255)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('form_url_sent_at')->nullable();
            $table->string('store_name', 255)->nullable();
            $table->string('contact_name', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('plan', 32)->nullable();
            $table->text('message')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_registrations');
    }
};
