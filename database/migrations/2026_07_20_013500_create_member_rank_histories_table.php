<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_rank_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('from_rank', 50)->nullable();
            $table->string('to_rank', 50);
            $table->string('from_rank_label', 100)->nullable();
            $table->string('to_rank_label', 100);
            $table->timestamp('achieved_at');
            $table->timestamps();

            $table->index(['member_id', 'achieved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_rank_histories');
    }
};
