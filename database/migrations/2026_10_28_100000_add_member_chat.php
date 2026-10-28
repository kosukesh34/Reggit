<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_feature_flags', function (Blueprint $table): void {
            $table->boolean('feature_chat_enabled')->default(false)->after('feature_tickets_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('project_feature_flags', function (Blueprint $table): void {
            $table->dropColumn('feature_chat_enabled');
        });
    }
};
