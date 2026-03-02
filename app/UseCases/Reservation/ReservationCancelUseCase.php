<?php

namespace App\UseCases\Reservation;

use App\Services\ReservationService;
use App\Services\UserService;
use App\UseCases\Reservation\Data\ReservationActionOutput;
use Illuminate\Http\Request;

class ReservationCancelUseCase
{
    public function __construct(
        private ReservationService $reservationService,
        private UserService $userService
    ) {}

    public function handle(Request $request, int $reservationId): ReservationActionOutput
    {
        $user = $this->userService->getOrCreateUser($request);
        if ($user->member === null) {
            return new ReservationActionOutput(true, null, null);
        }

        $reservation = $this->reservationService->cancelReservation($reservationId, $user);

        return new ReservationActionOutput(false, $reservation, null);
    }
}
