<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('coupons', 'image_fit')) {
            Schema::table('coupons', function (Blueprint $table): void {
                $table->string('image_fit', 16)->default('cover')->after('image_url');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('coupons', 'image_fit')) {
            Schema::table('coupons', function (Blueprint $table): void {
                $table->dropColumn('image_fit');
            });
        }
    }
};
