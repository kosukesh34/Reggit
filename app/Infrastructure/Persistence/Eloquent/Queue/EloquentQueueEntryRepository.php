<?php

namespace App\Infrastructure\Persistence\Eloquent\Queue;

use App\Domain\Queue\Aggregates\QueueEntry as QueueEntryAggregate;
use App\Domain\Queue\Repositories\QueueEntryRepository;
use App\Enums\QueueStatus;
use App\Models\QueueEntry as QueueEntryModel;
use App\Util\DateUtil;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class EloquentQueueEntryRepository implements QueueEntryRepository
{
    public function update(int $id, int $projectId, callable $change): ?QueueEntryAggregate
    {
        return $this->change(QueueEntryModel::query()->where('project_id', $projectId)->whereKey($id), $change);
    }

    public function updateNextWaiting(int $projectId, \DateTimeInterface $day, callable $change): ?QueueEntryAggregate
    {
        return $this->change(QueueEntryModel::query()
            ->where('project_id', $projectId)
            ->whereDate('created_at', DateUtil::nullableCarbon($day)?->toDateString() ?? $day->format('Y-m-d'))
            ->where('status', QueueStatus::WAITING)
            ->orderBy('queue_number'), $change);
    }

    /**
     * @param  Builder<QueueEntryModel>  $query
     * @param  callable(QueueEntryAggregate): void  $change
     */
    private function change(Builder $query, callable $change): ?QueueEntryAggregate
    {
        return DB::transaction(function () use ($query, $change): ?QueueEntryAggregate {
            $model = $query->lockForUpdate()->first();
            if ($model === null) {
                return null;
            }
            $entry = QueueEntryMapper::toDomain($model);
            $change($entry);
            $model->fill([
                'status' => $entry->status(),
                'called_at' => $entry->calledAt(),
                'entered_at' => $entry->enteredAt(),
            ]);
            if ($model->isDirty()) {
                $model->save();
            }

            return $entry;
        });
    }
}
