<?php

namespace App\UseCases\Admin\Reservation;

use App\Services\Admin\ReservationAdminService;

class AdminReservationUpdateStatusUseCase
{
    private ReservationAdminService $reservationAdminService;

    public function __construct(ReservationAdminService $reservationAdminService)
    {
        $this->reservationAdminService = $reservationAdminService;
    }

    public function handle(int $id, string $status): void
    {
        $this->reservationAdminService->updateStatus($id, $status);
    }
}
