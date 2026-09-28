<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_notice_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_notice_id')->constrained('system_notices')->cascadeOnDelete();
            $table->foreignId('admin_user_id')->constrained('admin_users')->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->unique(['system_notice_id', 'admin_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_notice_reads');
    }
};
