<?php

namespace App\Infrastructure\Persistence\Eloquent\Reservation;

use App\Domain\Reservation\Aggregates\Reservation as ReservationAggregate;
use App\Domain\Reservation\ValueObjects\ReservationId;
use App\Models\Reservation as ReservationModel;

final class ReservationMapper
{
    public static function toDomain(ReservationModel $model): ReservationAggregate
    {
        return new ReservationAggregate(
            new ReservationId((int) $model->id),
            (int) $model->project_id,
            (int) $model->user_id,
            (string) $model->status,
            $model->reserved_at,
            (int) $model->number_of_people,
            $model->notes,
        );
    }
}
