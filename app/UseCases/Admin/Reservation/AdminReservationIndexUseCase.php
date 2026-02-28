<?php

namespace App\UseCases\Admin\Reservation;

use App\Services\Admin\ReservationAdminService;
use App\UseCases\Admin\Reservation\Data\AdminReservationIndexOutput;

class AdminReservationIndexUseCase
{
    private const PER_PAGE = 20;

    private ReservationAdminService $reservationAdminService;

    public function __construct(ReservationAdminService $reservationAdminService)
    {
        $this->reservationAdminService = $reservationAdminService;
    }

    public function handle(?string $status, ?string $selectedDate, int $projectId): AdminReservationIndexOutput
    {
        $payload = $this->reservationAdminService->getReservationIndexPayload(
            $status,
            $selectedDate,
            $projectId,
            self::PER_PAGE
        );

        return new AdminReservationIndexOutput(
            $payload['reservations'],
            $payload['statusCounts'],
            $payload['selectedDate'] ?? null,
            $payload['selectedDateLabel'] ?? null,
            $payload['dateStatusCounts'] ?? null,
            $payload['timeSlotSummary'] ?? [],
            $payload['reservationCapacity'] ?? 0
        );
    }
}
