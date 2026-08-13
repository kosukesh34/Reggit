<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stamp_grant_codes', function (Blueprint $table) {
            $table->unsignedSmallInteger('usage_quota')->default(1)->after('usage_limit');
        });
    }

    public function down(): void
    {
        Schema::table('stamp_grant_codes', function (Blueprint $table) {
            $table->dropColumn('usage_quota');
        });
    }
};
