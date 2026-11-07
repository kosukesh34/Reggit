<?php

namespace App\UseCases\Reservation;

use App\UseCases\Reservation\Data\ReservationActionOutput;
use App\UseCases\Reservation\Data\ReservationUpdateInput;

class ReservationUpdateUseCase
{
    public function __construct(
        private ReservationCapacityLockedWriter $writer,
    ) {}

    public function handle(ReservationUpdateInput $input): ReservationActionOutput
    {
        return $this->writer->handle($input->user, $input->reservationPayload, $input->reservationId);
    }
}
