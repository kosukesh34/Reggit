<?php

namespace App\UseCases\Queue;

use App\Domain\Queue\Aggregates\QueueEntry as QueueEntryAggregate;
use App\Domain\Queue\Repositories\QueueEntryRepository;
use App\Enums\QueueStatus;
use App\Services\Admin\QueueAdminService;
use App\UseCases\Queue\Data\QueueEntry;
use App\UseCases\Queue\Data\QueueEntryFactory;

/** 指定した待機中エントリを呼び出す。 */
class QueueCallUseCase
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
                if ($aggregate->status() !== QueueStatus::WAITING) {
                    throw new \InvalidArgumentException('待機中の受付のみ呼び出しできます');
                }
                $aggregate->markAsCalled(now());
            },
        );
        if ($aggregate === null) {
            return null;
        }

        $this->queueAdminService->notifyCalled($aggregate->id()->value());

        $entry = $this->entryFactory->findModel($entryId, $projectId);

        return $entry === null ? null : $this->entryFactory->create(
            $entry,
            $this->statsService->getStats($projectId, $entry->queue_number),
        );
    }
}
