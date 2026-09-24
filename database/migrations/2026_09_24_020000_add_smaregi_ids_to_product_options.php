<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_option_groups', function (Blueprint $table) {
            $table->string('smaregi_option_group_id', 32)->nullable()->after('product_id');
            $table->unique(['project_id', 'product_id', 'smaregi_option_group_id'], 'product_option_groups_smaregi_unique');
        });
        Schema::table('product_options', function (Blueprint $table) {
            $table->string('smaregi_product_id', 32)->nullable()->after('group_id');
        });
    }

    public function down(): void
    {
        Schema::table('product_options', fn (Blueprint $table) => $table->dropColumn('smaregi_product_id'));
        Schema::table('product_option_groups', function (Blueprint $table) {
            $table->dropUnique('product_option_groups_smaregi_unique');
            $table->dropColumn('smaregi_option_group_id');
        });
    }
};
