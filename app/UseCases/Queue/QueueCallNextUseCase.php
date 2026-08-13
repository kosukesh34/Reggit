<?php

namespace App\UseCases\Queue;

use App\Domain\Queue\Repositories\QueueEntryRepository;
use App\Services\Admin\QueueAdminService;
use App\UseCases\Queue\Data\QueueEntry;
use App\UseCases\Queue\Data\QueueEntryFactory;

class QueueCallNextUseCase
{
    public function __construct(
        private QueueStatsService $statsService,
        private QueueEntryFactory $entryFactory,
        private QueueAdminService $queueAdminService,
        private QueueEntryRepository $queueEntryRepository,
    ) {}

    public function handle(int $projectId): ?QueueEntry
    {
        $aggregate = $this->queueEntryRepository->findNextWaiting($projectId, now());
        if ($aggregate === null) {
            return null;
        }

        $aggregate->markAsCalled(now());
        $this->queueEntryRepository->save($aggregate);
        $this->queueAdminService->notifyCalled($aggregate->id()->value());

        $entry = $this->entryFactory->findModel($aggregate->id()->value(), $projectId);
        if ($entry === null) {
            return null;
        }

        return $this->entryFactory->create(
            $entry,
            $this->statsService->getStats($projectId, $entry->queue_number),
        );
    }
}
