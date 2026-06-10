<?php

namespace App\UseCases\Reservation;

use App\Services\ReservationService;
use App\UseCases\Reservation\Data\ReservationActionOutput;
use App\UseCases\Reservation\Data\ReservationCancelInput;

class ReservationCancelUseCase
{
    public function __construct(
        private ReservationService $reservationService,
    ) {}

    public function handle(ReservationCancelInput $input): ReservationActionOutput
    {
        $user = $input->user;
        if ($user->member === null) {
            return new ReservationActionOutput(true, null, null);
        }

        $reservation = $this->reservationService->cancelReservation($input->reservationId, $user);

        return new ReservationActionOutput(false, $reservation, null);
    }
}
