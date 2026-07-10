<?php

namespace App\UseCases\Reservation;

use App\Models\User;
use App\Services\ReservationService;
use App\UseCases\Reservation\Data\ReservationActionOutput;

class ReservationCancelUseCase
{
    public function __construct(
        private ReservationService $reservationService,
    ) {}

    public function handle(User $user, int $reservationId): ReservationActionOutput
    {
        if ($user->member === null) {
            return new ReservationActionOutput(true, null, null);
        }

        $reservation = $this->reservationService->cancelReservation($reservationId, $user);

        return new ReservationActionOutput(false, $reservation, null);
    }
}
