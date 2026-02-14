<?php

namespace App\UseCases\Reservation;

use App\Services\ReservationService;
use App\Services\UserService;
use App\UseCases\Reservation\Dto\ReservationMemberResultDto;
use Illuminate\Http\Request;

class ReservationIndexUseCase
{
    public function __construct(
        private ReservationService $reservationService,
        private UserService $userService
    ) {
    }

    public function handle(Request $request): ReservationMemberResultDto
    {
        $user = $this->userService->getOrCreateUser($request);
        if ($user->member === null) {
            return new ReservationMemberResultDto(true, collect());
        }

        $reservations = $this->reservationService->getUserReservations($user);
        if (!$reservations instanceof \Illuminate\Support\Collection) {
            $reservations = collect($reservations);
        }

        return new ReservationMemberResultDto(false, $reservations);
    }
}
