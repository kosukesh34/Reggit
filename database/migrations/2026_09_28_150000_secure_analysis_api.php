<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('analysis_api_enabled')->default(false);
            $table->boolean('analysis_delivery_enabled')->default(false);
        });
        Schema::table('api_tokens', function (Blueprint $table) {
            $table->string('scope', 30)->default('general');
        });
    }

    public function down(): void
    {
        Schema::table('api_tokens', fn (Blueprint $table) => $table->dropColumn('scope'));
        Schema::table('projects', fn (Blueprint $table) => $table->dropColumn(['analysis_api_enabled', 'analysis_delivery_enabled']));
    }
};
