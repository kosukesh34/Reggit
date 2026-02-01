<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_stamps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->onDelete('cascade');
            $table->foreignId('stamp_card_id')->constrained()->onDelete('cascade');
            $table->integer('current_stamps')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['member_id', 'stamp_card_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_stamps');
    }
};
