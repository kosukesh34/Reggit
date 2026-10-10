<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smaregi_rank_settings_syncs', function (Blueprint $table) {
            $table->foreignId('project_id')->primary()->constrained()->cascadeOnDelete();
            $table->uuid('request_token');
            $table->timestamp('retry_at')->index();
            $table->timestamp('leased_until')->nullable();
            $table->uuid('lease_token')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smaregi_rank_settings_syncs');
    }
};
