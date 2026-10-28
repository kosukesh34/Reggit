<?php

namespace App\UseCases\Reservation;

use App\Domain\Reservation\Policies\ReservationSlotPolicy;
use App\Domain\Reservation\Repositories\ReservationRepository;
use App\Domain\Settings\Repositories\ReservationSettings;
use App\Models\ReservationCourse;
use App\UseCases\Reservation\Data\ReservationAvailability;
use App\Util\AdminContext;
use Carbon\Carbon;

class ReservationAvailabilityService
{
    public function __construct(
        private ReservationRepository $reservationRepository,
        private ReservationSettings $shopSettingRepository,
        private ReservationSlotPolicy $reservationSlotPolicy,
    ) {}

    /** @param  int  $numberOfPeople  人数で枠を数える設定のとき、この人数が入る枠だけを返す */
    public function getAvailableDates(?int $excludeReservationId, int $numberOfPeople = 1): ReservationAvailability
    {
        $projectId = AdminContext::requireCurrentProjectId();
        $rules = $this->shopSettingRepository->getReservationRules($projectId);

        $now = now();
        $startDate = $this->reservationSlotPolicy->firstBookableDay($now, $rules);
        $endDate = $this->reservationSlotPolicy->lastBookableDay($now, $rules);
        $usedSeats = $this->reservationRepository->getActiveSlotCounts(
            $projectId,
            $startDate,
            $endDate,
            $excludeReservationId,
            $rules->capacityByPeople,
        );

        $dateAvailability = [];
        foreach ($this->reservationSlotPolicy->bookableDates($now, $rules) as $date) {
            $dateStr = $date->format(ReservationSlotPolicy::DATE_FORMAT);
            $dateAvailability[$dateStr] = $this->shopSettingRepository->isDateAvailable($dateStr, $projectId);
        }

        return new ReservationAvailability(
            $this->reservationSlotPolicy->buildAvailableDates($now, $rules, $dateAvailability, $usedSeats, $numberOfPeople),
            $startDate->format(ReservationSlotPolicy::DATE_FORMAT),
            $endDate->format(ReservationSlotPolicy::DATE_FORMAT),
            ReservationCourse::where('project_id', $projectId)->where('is_active', true)
                ->orderBy('sort_order')->orderBy('id')->get(['id', 'name', 'price', 'description'])->toArray(),
            $rules->maxPartySize,
            $rules->changeDeadlineHours,
        );
    }

    public function getAvailabilityError(Carbon $reservedAt, ?int $excludeReservationId, int $numberOfPeople): ?string
    {
        $projectId = AdminContext::requireCurrentProjectId();
        $rules = $this->shopSettingRepository->getReservationRules($projectId);
        $dateStr = $reservedAt->format(ReservationSlotPolicy::DATE_FORMAT);

        $usedSeats = $this->reservationRepository->countActiveInSlot(
            $projectId,
            $reservedAt->copy()->startOfMinute(),
            $reservedAt->copy()->endOfMinute(),
            $excludeReservationId,
            $rules->capacityByPeople,
        );

        return $this->reservationSlotPolicy->availabilityError(
            $reservedAt,
            now(),
            $rules,
            $this->shopSettingRepository->isDateAvailable($dateStr, $projectId),
            $numberOfPeople,
            $usedSeats,
        );
    }
}
