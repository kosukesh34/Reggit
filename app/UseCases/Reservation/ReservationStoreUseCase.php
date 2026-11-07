<?php

namespace App\UseCases\Reservation;

use App\UseCases\Reservation\Data\ReservationActionOutput;
use App\UseCases\Reservation\Data\ReservationStoreInput;

class ReservationStoreUseCase
{
    public function __construct(
        private ReservationCapacityLockedWriter $writer,
    ) {}

    public function handle(ReservationStoreInput $input): ReservationActionOutput
    {
        return $this->writer->handle($input->user, $input->reservationPayload, null);
    }
}
