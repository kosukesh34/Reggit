<?php

namespace App\UseCases\Admin\Reservation;

use App\Models\Reservation;
use App\Services\Admin\ReservationAdminService;

class AdminReservationDetailUseCase
{
    private ReservationAdminService $reservationAdminService;

    public function __construct(ReservationAdminService $reservationAdminService)
    {
        $this->reservationAdminService = $reservationAdminService;
    }

    public function handle(int $id): Reservation
    {
        return $this->reservationAdminService->findOrFail($id);
    }
}
