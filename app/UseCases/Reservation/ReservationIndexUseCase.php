<?php

namespace App\UseCases\Reservation;

use App\Models\User;
use App\Services\ReservationService;
use App\UseCases\Reservation\Data\ReservationMemberOutput;

class ReservationIndexUseCase
{
    public function __construct(
        private ReservationService $reservationService,
    ) {}

    public function handle(User $user): ReservationMemberOutput
    {
        if ($user->member === null) {
            return new ReservationMemberOutput(true, collect());
        }

        return new ReservationMemberOutput(false, collect($this->reservationService->getUserReservations($user)));
    }
}
