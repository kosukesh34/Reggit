<?php

use App\Models\Product;
use App\Services\Admin\ProductDeduplicationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $projectIds = Product::withoutGlobalScopes()->distinct()->pluck('project_id');
        $dedupe = app(ProductDeduplicationService::class);
        foreach ($projectIds as $projectId) {
            $dedupe->deduplicateForProject((int) $projectId);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->unique(['project_id', 'name'], 'products_project_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_project_name_unique');
        });
    }
};
