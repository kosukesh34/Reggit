<?php

namespace App\Infrastructure\Persistence\Eloquent\Queue;

use App\Domain\Queue\Aggregates\QueueEntry as QueueEntryAggregate;
use App\Domain\Queue\Repositories\QueueEntryRepository;
use App\Enums\QueueStatus;
use App\Models\QueueEntry as QueueEntryModel;
use App\Util\DateUtil;

class EloquentQueueEntryRepository implements QueueEntryRepository
{
    public function findById(int $id, int $projectId): ?QueueEntryAggregate
    {
        $model = QueueEntryModel::query()
            ->where('project_id', $projectId)
            ->whereKey($id)
            ->first();

        if ($model === null) {
            return null;
        }

        return QueueEntryMapper::toDomain($model);
    }

    public function findNextWaiting(int $projectId, \DateTimeInterface $day): ?QueueEntryAggregate
    {
        $model = QueueEntryModel::query()
            ->where('project_id', $projectId)
            ->whereDate('created_at', DateUtil::nullableCarbon($day)?->toDateString() ?? $day->format('Y-m-d'))
            ->where('status', QueueStatus::WAITING)
            ->orderBy('queue_number')
            ->first();

        if ($model === null) {
            return null;
        }

        return QueueEntryMapper::toDomain($model);
    }

    public function save(QueueEntryAggregate $entry): void
    {
        $model = QueueEntryModel::query()
            ->whereKey($entry->id()->value())
            ->where('project_id', $entry->projectId())
            ->first();
        if ($model === null) {
            throw new \RuntimeException('QueueEntry not found for save: '.$entry->id()->value());
        }

        $model->update([
            'status' => $entry->status(),
            'called_at' => $entry->calledAt(),
            'entered_at' => $entry->enteredAt(),
        ]);
    }
}
