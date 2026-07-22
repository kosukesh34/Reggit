<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 既存の重複があれば、古い方を残して新しい方の queue_number をずらす
        $duplicates = DB::table('queue_entries')
            ->select('project_id', 'queue_number', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('queue_number')
            ->groupBy('project_id', 'queue_number')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($duplicates as $dup) {
            $ids = DB::table('queue_entries')
                ->where('project_id', $dup->project_id)
                ->where('queue_number', $dup->queue_number)
                ->orderBy('id')
                ->pluck('id');

            $ids->shift();
            foreach ($ids as $id) {
                $offset = 0;
                do {
                    $offset++;
                    $candidate = (int) $dup->queue_number + $offset;
                    $exists = DB::table('queue_entries')
                        ->where('project_id', $dup->project_id)
                        ->where('queue_number', $candidate)
                        ->exists();
                } while ($exists);

                DB::table('queue_entries')
                    ->where('id', $id)
                    ->update(['queue_number' => $candidate]);
            }
        }

        Schema::table('queue_entries', function (Blueprint $table) {
            $table->unique(['project_id', 'queue_number'], 'queue_entries_project_queue_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('queue_entries', function (Blueprint $table) {
            $table->dropUnique('queue_entries_project_queue_number_unique');
        });
    }
};
