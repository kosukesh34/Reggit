<?php

namespace App\UseCases\Queue;

use App\Domain\Queue\Aggregates\QueueEntry as QueueEntryAggregate;
use App\Domain\Queue\Repositories\QueueEntryRepository;
use App\Services\Admin\QueueAdminService;
use App\UseCases\Queue\Data\QueueEntry;
use App\UseCases\Queue\Data\QueueEntryFactory;

/** 呼び出し済みエントリへの再通知。 */
class QueueRecallUseCase
{
    public function __construct(
        private QueueStatsService $statsService,
        private QueueEntryFactory $entryFactory,
        private QueueAdminService $queueAdminService,
        private QueueEntryRepository $queueEntryRepository,
    ) {}

    public function handle(int $entryId, int $projectId): ?QueueEntry
    {
        $aggregate = $this->queueEntryRepository->update($entryId, $projectId,
            function (QueueEntryAggregate $aggregate): void {
                $aggregate->recall(now());
            },
        );
        if ($aggregate === null) {
            return null;
        }

        $this->queueAdminService->notifyCalled($aggregate->id()->value());

        $entry = $this->entryFactory->findModel($entryId, $projectId);
        if ($entry === null) {
            return null;
        }

        return $this->entryFactory->create(
            $entry,
            $this->statsService->getStats($projectId, $entry->queue_number),
        );
    }
}
