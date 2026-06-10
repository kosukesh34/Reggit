<?php

namespace App\UseCases\Reservation;

use App\Services\ReservationService;
use App\UseCases\Reservation\Data\ReservationActionOutput;
use App\UseCases\Reservation\Data\ReservationStoreInput;
use App\Util\DateUtil;

class ReservationStoreUseCase
{
    public function __construct(
        private ReservationService $reservationService,
        private ReservationAvailabilityService $availabilityService,
    ) {}

    public function handle(ReservationStoreInput $input): ReservationActionOutput
    {
        $user = $input->user;
        if ($user->member === null) {
            return new ReservationActionOutput(true, null, null);
        }

        $validated = $input->validated;
        $reservedAt = DateUtil::nullableCarbon($validated['reserved_at']);
        $availabilityError = $this->availabilityService->getAvailabilityError($reservedAt, null);
        if ($availabilityError !== null) {
            return new ReservationActionOutput(false, null, $availabilityError);
        }

        $validated['reserved_at'] = $reservedAt->format('Y-m-d H:i:s');
        $reservation = $this->reservationService->createReservation($user, $validated);

        return new ReservationActionOutput(false, $reservation, null);
    }
}
