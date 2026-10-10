<?php

namespace App\UseCases\Queue;

use App\Domain\Queue\Aggregates\QueueEntry as QueueEntryAggregate;
use App\Domain\Queue\Repositories\QueueEntryRepository;
use App\Enums\QueueStatus;
use App\UseCases\Queue\Data\QueueEntry;
use App\UseCases\Queue\Data\QueueEntryFactory;

/** 呼び出し済み（または待機中）を入店済みにする。 */
class QueueEnterUseCase
{
    public function __construct(
        private QueueStatsService $statsService,
        private QueueEntryFactory $entryFactory,
        private QueueEntryRepository $queueEntryRepository,
    ) {}

    public function handle(int $entryId, int $projectId): ?QueueEntry
    {
        $aggregate = $this->queueEntryRepository->update($entryId, $projectId,
            function (QueueEntryAggregate $aggregate): void {
                if (! in_array($aggregate->status(), [QueueStatus::WAITING, QueueStatus::CALLED], true)) {
                    throw new \InvalidArgumentException('入店にできない受付です');
                }
                $aggregate->markAsEntered(now());
            },
        );
        if ($aggregate === null) {
            return null;
        }

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
