<?php

namespace App\UseCases\Reservation;

use App\Services\ReservationService;
use App\UseCases\Member\Data\MemberUserInput;
use App\UseCases\Reservation\Data\ReservationMemberOutput;
use Illuminate\Support\Collection;

class ReservationIndexUseCase
{
    public function __construct(
        private ReservationService $reservationService,
    ) {}

    public function handle(MemberUserInput $input): ReservationMemberOutput
    {
        $user = $input->user;
        if ($user->member === null) {
            return new ReservationMemberOutput(true, collect());
        }

        $reservations = $this->reservationService->getUserReservations($user);
        if (! ($reservations instanceof Collection)) {
            $reservations = collect($reservations);
        }

        return new ReservationMemberOutput(false, $reservations);
    }
}
