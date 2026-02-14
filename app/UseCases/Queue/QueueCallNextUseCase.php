<?php

namespace App\UseCases\Queue;

use App\Enums\QueueStatus;
use App\Models\QueueEntry;
use App\Services\Admin\QueueAdminService;
use App\UseCases\Queue\Dto\QueueEntryDto;
use App\UseCases\Queue\Dto\QueueEntryDtoFactory;
use App\Util\AdminContext;

class QueueCallNextUseCase
{
    public function __construct(
        private QueueStatsService $statsService,
        private QueueEntryDtoFactory $dtoFactory,
        private QueueAdminService $queueAdminService
    ) {
    }

    public function handle(): ?QueueEntryDto
    {
        $projectId = AdminContext::getCurrentProjectId() ?? 1;
        $entry = $this->getNextEntry($projectId);
        if ($entry === null) {
            return null;
        }

        $this->queueAdminService->updateStatus($entry->id, QueueStatus::CALLED);
        $entry->refresh();

        $stats = $this->statsService->getStats($projectId, $entry->queue_number);
        return $this->dtoFactory->create($entry, $stats);
    }

    private function getNextEntry(int $projectId): ?QueueEntry
    {
        return QueueEntry::where('project_id', $projectId)
            ->whereDate('created_at', today())
            ->where('status', QueueStatus::WAITING)
            ->orderBy('queue_number')
            ->first();
    }
}
