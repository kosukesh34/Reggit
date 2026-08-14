<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('announcements', 'delivery_timing')) {
            Schema::table('announcements', function (Blueprint $table): void {
                $table->string('delivery_timing', 16)->default('immediate')->after('delivery_type');
            });
        }

        DB::table('announcements')
            ->where('delivery_type', 'scheduled')
            ->update([
                'delivery_type' => 'immediate_bulk',
                'delivery_timing' => 'scheduled',
            ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('announcements', 'delivery_timing')) {
            DB::table('announcements')
                ->where('delivery_timing', 'scheduled')
                ->update(['delivery_type' => 'scheduled']);

            Schema::table('announcements', function (Blueprint $table): void {
                $table->dropColumn('delivery_timing');
            });
        }
    }
};
