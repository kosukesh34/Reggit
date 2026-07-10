<?php

namespace App\UseCases\Queue;

use App\Domain\Queue\Repositories\QueueEntryRepository;
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
        private QueueAdminService $queueAdminService,
        private QueueEntryRepository $queueEntryRepository,
    ) {}

    public function handle(): ?QueueEntry
    {
        $projectId = AdminContext::getCurrentProjectId() ?? 1;
        $aggregate = $this->queueEntryRepository->findNextWaiting((int) $projectId, now());
        if ($aggregate === null) {
            return null;
        }

        $aggregate->markAsCalled(now());
        $this->queueEntryRepository->save($aggregate);
        $this->queueAdminService->notifyCalled($aggregate->id()->value());

        $entry = QueueEntryModel::query()->findOrFail($aggregate->id()->value());
        $stats = $this->statsService->getStats($projectId, $entry->queue_number);

        return $this->entryFactory->create($entry, $stats);
    }
}
