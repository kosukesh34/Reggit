<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Reservation;
use App\Models\Scopes\StoreAccessScope;
use App\Support\DailySequenceNumber;

/**
 * 予約番号の日次採番（注文と同じ YYYYMMDD+通番 / 表示は日+通番）。
 */
class ReservationNumberAssigner
{
    public function buildNext(int $projectId): string
    {
        // The database sequence is shared by all stores in this project.
        Project::query()->whereKey($projectId)->lockForUpdate()->firstOrFail();
        $prefix = DailySequenceNumber::datePrefix();
        $maxSeq = 0;
        $existing = Reservation::withoutGlobalScope(StoreAccessScope::class)
            ->where('project_id', $projectId)
            ->where('reservation_number', 'like', $prefix.'%')
            ->lockForUpdate()->pluck('reservation_number');

        foreach ($existing as $reservationNumber) {
            $seq = DailySequenceNumber::parseSeq((string) $reservationNumber);
            if ($seq !== null) {
                $maxSeq = max($maxSeq, $seq);
            }
        }

        return DailySequenceNumber::build(DailySequenceNumber::nextSeq($maxSeq));
    }
}
