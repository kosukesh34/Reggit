<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_pass_update_counters', function (Blueprint $table) {
            $table->string('pass_type_identifier', 255)->primary();
            $table->unsignedBigInteger('revision')->default(0);
        });
        Schema::table('wallet_pass_updates', function (Blueprint $table) {
            $table->unsignedBigInteger('revision')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('wallet_pass_updates', function (Blueprint $table) {
            $table->dropColumn('revision');
        });
        Schema::dropIfExists('wallet_pass_update_counters');
    }
};
