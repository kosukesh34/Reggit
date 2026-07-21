<?php

namespace App\UseCases\Queue;

use App\Domain\Queue\Repositories\QueueEntryRepository;
use App\Enums\QueueStatus;
use App\Models\QueueEntry as QueueEntryModel;
use App\UseCases\Queue\Data\QueueEntry;
use App\UseCases\Queue\Data\QueueEntryFactory;
use App\Util\AdminContext;

/** 呼び出し済み（または待機中）を入店済みにする。 */
class QueueEnterUseCase
{
    public function __construct(
        private QueueStatsService $statsService,
        private QueueEntryFactory $entryFactory,
        private QueueEntryRepository $queueEntryRepository,
    ) {}

    public function handle(int $entryId): ?QueueEntry
    {
        $projectId = AdminContext::getCurrentProjectId() ?? 1;
        $aggregate = $this->queueEntryRepository->findById($entryId, (int) $projectId);
        if ($aggregate === null) {
            return null;
        }

        if (! in_array($aggregate->status(), [QueueStatus::WAITING, QueueStatus::CALLED], true)) {
            throw new \InvalidArgumentException('入店にできない受付です');
        }

        $aggregate->markAsEntered(now());
        $this->queueEntryRepository->save($aggregate);

        $entry = QueueEntryModel::query()->with('user.devices')->findOrFail($entryId);
        $stats = $this->statsService->getStats($projectId, $entry->queue_number);

        return $this->entryFactory->create($entry, $stats);
    }
}
