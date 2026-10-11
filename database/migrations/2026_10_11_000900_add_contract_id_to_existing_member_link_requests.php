<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('existing_member_link_requests', function (Blueprint $table) {
            $table->string('contract_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('existing_member_link_requests', function (Blueprint $table) {
            $table->dropColumn('contract_id');
        });
    }
};
