<?php

namespace App\Domain\Queue\Repositories;

use App\Domain\Queue\Aggregates\QueueEntry;

interface QueueEntryRepository
{
    public function findById(int $id, int $projectId): ?QueueEntry;

    public function findNextWaiting(int $projectId, \DateTimeInterface $day): ?QueueEntry;

    public function save(QueueEntry $entry): void;
}
