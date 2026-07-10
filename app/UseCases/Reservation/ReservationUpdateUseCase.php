<?php

namespace App\UseCases\Reservation;

use App\Services\ReservationService;
use App\UseCases\Reservation\Data\ReservationActionOutput;
use App\UseCases\Reservation\Data\ReservationUpdateInput;
use Carbon\Carbon;

class ReservationUpdateUseCase
{
    public function __construct(
        private ReservationService $reservationService,
        private ReservationAvailabilityService $availabilityService,
    ) {}

    public function handle(ReservationUpdateInput $input): ReservationActionOutput
    {
        if ($input->user->member === null) {
            return new ReservationActionOutput(true, null, null);
        }

        $reservationPayload = $input->reservationPayload;
        $reservedAt = Carbon::parse($reservationPayload['reserved_at']);
        $availabilityError = $this->availabilityService->getAvailabilityError($reservedAt, $input->reservationId);
        if ($availabilityError !== null) {
            return new ReservationActionOutput(false, null, $availabilityError);
        }

        $reservationPayload['reserved_at'] = $reservedAt->format('Y-m-d H:i:s');
        $reservation = $this->reservationService->updateReservation($input->reservationId, $input->user, $reservationPayload);

        return new ReservationActionOutput(false, $reservation, null);
    }
}
