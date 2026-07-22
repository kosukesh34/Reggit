<?php

namespace App\UseCases\Queue;

use App\Models\QueueEntry as QueueEntryModel;
use App\UseCases\Queue\Data\QueueEntry;
use App\UseCases\Queue\Data\QueueEntryFactory;

/** 待ち行列エントリ1件のステータス（待ち人数・予測時間含む）を取得する。 */
class QueueStatusUseCase
{
    public function __construct(
        private QueueStatsService $statsService,
        private QueueEntryFactory $entryFactory
    ) {}

    public function handle(int $projectId, int $entryId): ?QueueEntry
    {
        $entry = QueueEntryModel::where('project_id', $projectId)
            ->whereKey($entryId)
            ->with('user.devices')
            ->first();

        if ($entry === null) {
            return null;
        }

        $stats = $this->statsService->getStats($projectId, $entry->queue_number);

        return $this->entryFactory->create($entry, $stats);
    }
}
