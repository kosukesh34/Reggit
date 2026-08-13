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
        if ($input->user->member === null) {
            return new ReservationActionOutput(true, null, null);
        }

        $reservationPayload = $input->reservationPayload;
        $reservedAt = DateUtil::nullableCarbon($reservationPayload['reserved_at']);
        if ($reservedAt === null) {
            return new ReservationActionOutput(false, null, '予約日時が不正です');
        }
        $availabilityError = $this->availabilityService->getAvailabilityError($reservedAt, null);
        if ($availabilityError !== null) {
            return new ReservationActionOutput(false, null, $availabilityError);
        }

        $reservationPayload['reserved_at'] = $reservedAt->format('Y-m-d H:i:s');
        $reservation = $this->reservationService->createReservation($input->user, $reservationPayload);

        return new ReservationActionOutput(false, $reservation, null);
    }
}
