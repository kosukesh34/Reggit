<?php

namespace App\UseCases\Queue;

use App\Domain\Queue\Repositories\QueueEntryRepository;
use App\Models\QueueEntry;

class QueueCancelUseCase
{
    public function __construct(
        private QueueEntryRepository $queueEntryRepository,
    ) {}

    public function handle(int $id, int $projectId): ?QueueEntry
    {
        $aggregate = $this->queueEntryRepository->findById($id, $projectId);
        if ($aggregate === null) {
            return null;
        }

        $aggregate->cancel();
        $this->queueEntryRepository->save($aggregate);

        return QueueEntry::query()->whereKey($aggregate->id()->value())->first();
    }
}
