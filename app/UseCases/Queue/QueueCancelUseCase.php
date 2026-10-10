<?php

namespace App\UseCases\Queue;

use App\Domain\Queue\Aggregates\QueueEntry;
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
        $conflict = false;
        $aggregate = $this->queueEntryRepository->update($id, $projectId,
            function (QueueEntry $aggregate) use (&$conflict): void {
                if ($aggregate->status() !== QueueStatus::CANCELLED && ! $aggregate->canCancel()) {
                    $conflict = true;

                    return;
                }
                $aggregate->cancel();
            },
        );
        if ($aggregate === null) {
            return new QueueCancelOutput(null, false);
        }

        return new QueueCancelOutput($this->entryFactory->findModel($id, $projectId), $conflict);
    }
}
