<?php

namespace App\UseCases\Reservation\Data;

use Illuminate\Support\Collection;

/** 予約一覧（会員）UseCase の出力。不変。 @param Collection<int, \App\Models\Reservation> $reservations */
class ReservationMemberOutput
{
    public function __construct(
        public bool $requiresMembership,
        public Collection $reservations
    ) {}
}
