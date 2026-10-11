<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smaregi_member_profile_syncs', function (Blueprint $table) {
            $table->foreignId('member_id')->primary()->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('customer_id');
            $table->string('contract_id');
            $table->json('body');
            $table->json('field_keys');
            $table->uuid('request_token');
            $table->timestamp('retry_at')->index();
            $table->timestamp('leased_until')->nullable();
            $table->uuid('lease_token')->nullable();
            $table->timestamp('delivered_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smaregi_member_profile_syncs');
    }
};
