<?php

namespace App\Infrastructure\Persistence\Eloquent\Queue;

use App\Domain\Queue\Aggregates\QueueEntry as QueueEntryAggregate;
use App\Domain\Queue\ValueObjects\QueueEntryId;
use App\Models\QueueEntry as QueueEntryModel;

final class QueueEntryMapper
{
    public static function toDomain(QueueEntryModel $model): QueueEntryAggregate
    {
        return new QueueEntryAggregate(
            new QueueEntryId((int) $model->id),
            (int) $model->project_id,
            (string) $model->status,
            $model->called_at,
            $model->entered_at,
        );
    }
}
