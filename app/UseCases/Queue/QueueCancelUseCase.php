<?php

namespace App\UseCases\Queue;

use App\Domain\Queue\Repositories\QueueEntryRepository;
use App\Enums\QueueStatus;
use App\UseCases\Queue\Data\QueueCancelOutput;
use App\UseCases\Queue\Data\QueueEntryFactory;

class QueueCancelUseCase
{
    public function __construct(
        private QueueEntryRepository $queueEntryRepository,
        private QueueEntryFactory $entryFactory,
    ) {}

    public function handle(int $id, int $projectId): QueueCancelOutput
    {
        $aggregate = $this->queueEntryRepository->findById($id, $projectId);
        if ($aggregate === null) {
            return new QueueCancelOutput(null, false);
        }

        if ($aggregate->status() !== QueueStatus::CANCELLED && $aggregate->canCancel() === false) {
            return new QueueCancelOutput($this->entryFactory->findModel($id, $projectId), true);
        }

        $aggregate->cancel();
        $this->queueEntryRepository->save($aggregate);

        return new QueueCancelOutput($this->entryFactory->findModel($id, $projectId), false);
    }
}
