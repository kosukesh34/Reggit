<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kitchen_devices', function (Blueprint $table) {
            $table->unsignedBigInteger('store_id')->nullable()->after('project_id');
            $table->foreign(['project_id', 'store_id'])->references(['project_id', 'id'])->on('stores')->restrictOnDelete();
        });

        // Only a project with exactly one store has an unambiguous legacy device assignment.
        DB::table('stores')->select('project_id')->selectRaw('MIN(id) as store_id')
            ->groupBy('project_id')->havingRaw('COUNT(*) = 1')->orderBy('project_id')
            ->chunk(100, function ($stores): void {
                foreach ($stores as $store) {
                    DB::table('kitchen_devices')->where('project_id', $store->project_id)
                        ->whereNull('store_id')->update(['store_id' => $store->store_id]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('kitchen_devices', function (Blueprint $table) {
            $table->dropForeign(['project_id', 'store_id']);
            $table->dropColumn('store_id');
        });
    }
};
