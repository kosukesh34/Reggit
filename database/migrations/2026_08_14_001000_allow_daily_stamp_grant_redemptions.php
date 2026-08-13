<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stamp_grant_redemptions', function (Blueprint $table) {
            $table->index('stamp_grant_code_id', 'sgr_code_id_index');
        });
        Schema::table('stamp_grant_redemptions', function (Blueprint $table) {
            $table->dropUnique(['stamp_grant_code_id', 'member_id']);
            $table->index(['stamp_grant_code_id', 'member_id', 'created_at'], 'sgr_code_member_created_index');
        });
    }

    public function down(): void
    {
        Schema::table('stamp_grant_redemptions', function (Blueprint $table) {
            $table->dropIndex('sgr_code_member_created_index');
            $table->unique(['stamp_grant_code_id', 'member_id']);
        });
        Schema::table('stamp_grant_redemptions', function (Blueprint $table) {
            $table->dropIndex('sgr_code_id_index');
        });
    }
};
