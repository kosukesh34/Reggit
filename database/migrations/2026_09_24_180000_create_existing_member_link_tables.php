<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('existing_member_link_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('is_enabled')->default(false);
            $table->string('description', 300)->nullable();
            $table->json('identifier_fields');
            $table->timestamps();
        });

        Schema::create('existing_member_link_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('smaregi_customer_id', 64);
            $table->string('email')->nullable();
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'smaregi_customer_id'], 'eml_requests_project_customer_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('existing_member_link_requests');
        Schema::dropIfExists('existing_member_link_settings');
    }
};
