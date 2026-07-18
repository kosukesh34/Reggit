<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('queue_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('queue_number')->nullable()->change();
        });
        Schema::table('queue_call_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('queue_number')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('queue_entries', function (Blueprint $table) {
            $table->integer('queue_number')->nullable()->change();
        });
        Schema::table('queue_call_logs', function (Blueprint $table) {
            $table->unsignedInteger('queue_number')->nullable()->change();
        });
    }
};
