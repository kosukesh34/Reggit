<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('smaregi_product_id', 32)->nullable()->after('project_id');
            $table->unique(['project_id', 'smaregi_product_id'], 'products_project_smaregi_product_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_project_smaregi_product_id_unique');
            $table->dropColumn('smaregi_product_id');
        });
    }
};
