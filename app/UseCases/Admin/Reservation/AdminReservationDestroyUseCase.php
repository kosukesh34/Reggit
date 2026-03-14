<?php

namespace App\UseCases\Admin\Reservation;

use App\Services\Admin\ReservationAdminService;

class AdminReservationDestroyUseCase
{
    private ReservationAdminService $service;

    public function __construct(ReservationAdminService $service)
    {
        $this->service = $service;
    }

    public function handle(int $id): void
    {
        $this->service->destroy($id);
    }
}
