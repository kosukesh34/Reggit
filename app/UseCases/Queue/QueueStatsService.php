<?php

namespace App\UseCases\Queue;

use App\Enums\QueueStatus;
use App\Models\QueueEntry;
use App\Models\ShopSetting;

class QueueStatsService
{
    public function getStats(int $projectId, ?int $beforeQueueNumber): array
    {
        $base = QueueEntry::where('project_id', $projectId)
            ->whereDate('created_at', today())
            ->where('status', QueueStatus::WAITING);

        $waitingCount = (clone $base)->count();
        $aheadCount = $this->getAheadCount($beforeQueueNumber, $base);
        $avgMinutes = ShopSetting::getQueueAvgServeMinutes($projectId);
        $estimatedMinutes = $this->estimateMinutes($waitingCount, $aheadCount, $beforeQueueNumber, $avgMinutes);

        return [
            'waiting_count' => $waitingCount,
            'ahead_count' => $aheadCount,
            'estimated_wait_minutes' => $estimatedMinutes,
        ];
    }

    private function getAheadCount(?int $beforeQueueNumber, $base): ?int
    {
        if ($beforeQueueNumber === null) {
            return null;
        }

        return (clone $base)->where('queue_number', '<', $beforeQueueNumber)->count();
    }

    private function estimateMinutes(int $waitingCount, ?int $aheadCount, ?int $beforeQueueNumber, int $avgMinutes): int
    {
        if ($beforeQueueNumber === null) {
            return $waitingCount * $avgMinutes;
        }
        $count = $aheadCount ?? 0;

        return $count * $avgMinutes;
    }
}
