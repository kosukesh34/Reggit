<?php

namespace App\UseCases\Reservation\Data;

use Illuminate\Support\Collection;

/** 予約一覧（会員）UseCase の実行結果。不変。 @param Collection<int, \App\Models\Reservation> $reservations */
class ReservationMemberResult
{
    public function __construct(
        public bool $requiresMembership,
        public Collection $reservations
    ) {
    }
}
