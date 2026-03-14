<?php

namespace App\UseCases\Admin\Reservation;

use App\Models\Reservation;
use App\Services\Admin\ReservationAdminService;

class AdminReservationStoreUseCase
{
    private ReservationAdminService $service;

    public function __construct(ReservationAdminService $service)
    {
        $this->service = $service;
    }

    public function handle(array $data, int $projectId): Reservation
    {
        return $this->service->store($data, $projectId);
    }
}
