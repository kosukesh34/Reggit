<?php

namespace App\UseCases\Reservation;

use App\Domain\Reservation\Policies\ReservationSlotPolicy;
use App\Domain\Reservation\Repositories\ReservationRepository;
use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\UseCases\Reservation\Data\ReservationAvailability;
use App\Util\AdminContext;
use Carbon\Carbon;

class ReservationAvailabilityService
{
    public function __construct(
        private ReservationRepository $reservationRepository,
        private ShopSettingRepository $shopSettingRepository,
        private ReservationSlotPolicy $reservationSlotPolicy,
    ) {}

    public function getAvailableDates(?int $excludeReservationId): ReservationAvailability
    {
        $projectId = (int) (AdminContext::getCurrentProjectId() ?? 1);
        $timeSlots = $this->shopSettingRepository->getReservationTimeSlots($projectId);
        $advanceDays = $this->shopSettingRepository->getAdvanceBookingDays($projectId);
        $reservationCapacity = $this->shopSettingRepository->getReservationCapacityPerSlot($projectId);

        $today = now();
        $startDate = $today->copy()->addDay()->startOfDay();
        $endDate = $today->copy()->addDays($advanceDays)->endOfDay();
        $slotCounts = $this->reservationRepository->getActiveSlotCounts(
            $projectId,
            $startDate,
            $endDate,
            $excludeReservationId,
        );

        $dateAvailability = [];
        for ($i = 1; $i <= $advanceDays; $i++) {
            $dateStr = $today->copy()->addDays($i)->format(ReservationSlotPolicy::DATE_FORMAT);
            $dateAvailability[$dateStr] = $this->shopSettingRepository->isDateAvailable($dateStr, $projectId);
        }

        $availableDates = $this->reservationSlotPolicy->buildAvailableDates(
            $today,
            $advanceDays,
            $timeSlots,
            $dateAvailability,
            $slotCounts,
            $reservationCapacity,
        );

        return new ReservationAvailability(
            $availableDates,
            $startDate->format(ReservationSlotPolicy::DATE_FORMAT),
            $endDate->format(ReservationSlotPolicy::DATE_FORMAT)
        );
    }

    public function getAvailabilityError(Carbon $reservedAt, ?int $excludeReservationId): ?string
    {
        $projectId = (int) (AdminContext::getCurrentProjectId() ?? 1);
        $advanceDays = $this->shopSettingRepository->getAdvanceBookingDays($projectId);
        $timeSlots = $this->shopSettingRepository->getReservationTimeSlots($projectId);
        $reservationCapacity = $this->shopSettingRepository->getReservationCapacityPerSlot($projectId);
        $dateStr = $reservedAt->format(ReservationSlotPolicy::DATE_FORMAT);

        $slotStart = $reservedAt->copy()->startOfMinute();
        $slotEnd = $reservedAt->copy()->endOfMinute();
        $currentSlotCount = $this->reservationRepository->countActiveInSlot(
            $projectId,
            $slotStart,
            $slotEnd,
            $excludeReservationId,
        );

        return $this->reservationSlotPolicy->availabilityError(
            $reservedAt,
            now(),
            $advanceDays,
            $timeSlots,
            $this->shopSettingRepository->isDateAvailable($dateStr, $projectId),
            $reservationCapacity,
            $currentSlotCount,
        );
    }
}
