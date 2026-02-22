<?php

namespace App\UseCases\Reservation\Data;

use App\Models\Reservation;

/** 予約の作成・更新・キャンセル UseCase の出力。不変。 */
class ReservationActionOutput
{
    public function __construct(
        public bool $requiresMembership,
        public ?Reservation $reservation,
        public ?string $availabilityError
    ) {
    }
}
