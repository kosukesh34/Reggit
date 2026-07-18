<?php

namespace App\Services;

use App\Models\Reservation;
use App\Support\DailySequenceNumber;

/**
 * 予約番号の日次採番（注文と同じ YYYYMMDD+通番 / 表示は日+通番）。
 */
class ReservationNumberAssigner
{
    public function buildNext(int $projectId): string
    {
        $prefix = DailySequenceNumber::datePrefix();
        $maxSeq = 0;
        $existing = Reservation::query()
            ->where('project_id', $projectId)
            ->where('reservation_number', 'like', $prefix.'%')
            ->pluck('reservation_number');

        foreach ($existing as $reservationNumber) {
            $seq = DailySequenceNumber::parseSeq((string) $reservationNumber);
            if ($seq !== null) {
                $maxSeq = max($maxSeq, $seq);
            }
        }

        return DailySequenceNumber::build(DailySequenceNumber::nextSeq($maxSeq));
    }
}
