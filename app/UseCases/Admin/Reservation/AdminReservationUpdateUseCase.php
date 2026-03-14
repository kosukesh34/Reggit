<?php

namespace App\UseCases\Admin\Reservation;

use App\Models\Reservation;
use App\Services\Admin\ReservationAdminService;

class AdminReservationUpdateUseCase
{
    private ReservationAdminService $service;

    public function __construct(ReservationAdminService $service)
    {
        $this->service = $service;
    }

    public function handle(int $id, array $data): Reservation
    {
        return $this->service->update($id, $data);
    }
}
