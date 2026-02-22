<?php

namespace App\UseCases\Queue;

use App\Enums\QueueStatus;
use App\Models\QueueEntry as QueueEntryModel;
use App\Services\Admin\QueueAdminService;
use App\UseCases\Queue\Data\QueueEntry;
use App\UseCases\Queue\Data\QueueEntryFactory;
use App\Util\AdminContext;

class QueueCallNextUseCase
{
    public function __construct(
        private QueueStatsService $statsService,
        private QueueEntryFactory $entryFactory,
        private QueueAdminService $queueAdminService
    ) {
    }

    public function handle(): ?QueueEntry
    {
        $projectId = AdminContext::getCurrentProjectId() ?? 1;
        $entry = $this->getNextEntry($projectId);
        if ($entry === null) {
            return null;
        }

        $this->queueAdminService->updateStatus($entry->id, QueueStatus::CALLED);
        $entry->refresh();

        $stats = $this->statsService->getStats($projectId, $entry->queue_number);
        return $this->entryFactory->create($entry, $stats);
    }

    private function getNextEntry(int $projectId): ?QueueEntryModel
    {
        return QueueEntryModel::where('project_id', $projectId)
            ->whereDate('created_at', today())
            ->where('status', QueueStatus::WAITING)
            ->orderBy('queue_number')
            ->first();
    }
}
