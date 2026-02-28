<?php

namespace App\UseCases\Admin\Reservation;

use App\Services\Admin\ReservationAdminService;

class AdminReservationCompleteUseCase
{
    private ReservationAdminService $reservationAdminService;

    public function __construct(ReservationAdminService $reservationAdminService)
    {
        $this->reservationAdminService = $reservationAdminService;
    }

    public function handle(int $id): void
    {
        $this->reservationAdminService->complete($id);
    }
}
