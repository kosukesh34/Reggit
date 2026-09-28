<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_store_id')->nullable();
            $table->foreign(['project_id', 'customer_store_id'])->references(['project_id', 'id'])->on('stores')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['project_id', 'customer_store_id']);
            $table->dropColumn('customer_store_id');
        });
    }
};
