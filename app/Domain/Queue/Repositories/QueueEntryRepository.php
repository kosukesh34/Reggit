<?php

namespace App\Domain\Queue\Repositories;

use App\Domain\Queue\Aggregates\QueueEntry;

interface QueueEntryRepository
{
    /** @param callable(QueueEntry): void $change */
    public function update(int $id, int $projectId, callable $change): ?QueueEntry;

    /** @param callable(QueueEntry): void $change */
    public function updateNextWaiting(int $projectId, \DateTimeInterface $day, callable $change): ?QueueEntry;
}
