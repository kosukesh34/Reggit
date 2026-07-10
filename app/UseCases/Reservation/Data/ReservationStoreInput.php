<?php

namespace App\UseCases\Reservation\Data;

use App\Models\User;

/**
 * 予約作成 UseCase への入力。検証済み。不変。
 *
 * @param  array{
 *     reserved_at: string,
 *     number_of_people: int,
 *     notes?: string|null,
 * }  $reservationPayload
 */
class ReservationStoreInput
{
    public function __construct(
        public readonly User $user,
        public readonly array $reservationPayload,
    ) {}
}
