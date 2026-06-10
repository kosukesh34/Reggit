<?php

namespace App\Services\Admin;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\ShopSetting;
use App\Util\DateUtil;

class ReservationAdminService
{
    public function getReservationIndexPayload(?string $status, ?string $selectedDate, int $projectId, int $perPage = 20): array
    {
        $query = Reservation::with('user')->orderByDesc('reserved_at');
        if ($status !== null && $status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }
        if ($selectedDate !== null && $selectedDate !== '') {
            $query->whereDate('reserved_at', $selectedDate);
        }
        $reservations = $query->paginate($perPage);

        $statusCounts = $this->getStatusCounts();

        $dateStatusCounts = null;
        $timeSlotSummary = [];
        $selectedDateLabel = null;
        $reservationCapacity = ShopSetting::getReservationCapacityPerSlot($projectId);

        if ($selectedDate !== null && $selectedDate !== '') {
            $date = DateUtil::nullableCarbon($selectedDate);
            $selectedDateLabel = $date->format('Y年m月d日');
            $dateStatusCounts = $this->getStatusCounts($selectedDate);
            $timeSlots = ShopSetting::getReservationTimeSlots($projectId);
            foreach ($timeSlots as $slot) {
                $slotStart = DateUtil::nullableCarbon($date->format('Y-m-d').' '.$slot);
                $slotEnd = $slotStart->copy()->addHour();
                $count = Reservation::whereBetween('reserved_at', [$slotStart, $slotEnd])->count();
                $timeSlotSummary[] = [
                    'time' => $slot,
                    'count' => $count,
                    'capacity' => $reservationCapacity,
                    'is_full' => $count >= $reservationCapacity,
                ];
            }
        }

        return compact(
            'reservations',
            'statusCounts',
            'selectedDate',
            'selectedDateLabel',
            'dateStatusCounts',
            'timeSlotSummary',
            'reservationCapacity'
        );
    }

    public function getStatusCounts(?string $date = null): array
    {
        $query = Reservation::query();
        if ($date !== null && $date !== '') {
            $query->whereDate('reserved_at', $date);
        }

        return [
            'all' => (clone $query)->count(),
            'pending' => (clone $query)->where('status', ReservationStatus::PENDING)->count(),
            'confirmed' => (clone $query)->where('status', ReservationStatus::CONFIRMED)->count(),
            'completed' => (clone $query)->where('status', ReservationStatus::COMPLETED)->count(),
            'cancelled' => (clone $query)->where('status', ReservationStatus::CANCELLED)->count(),
        ];
    }

    public function findOrFail(int $id): Reservation
    {
        return Reservation::with('user')->findOrFail($id);
    }

    public function updateStatus(int $id, string $status): void
    {
        Reservation::whereKey($id)->update(['status' => $status]);
    }

    public function complete(int $id): void
    {
        Reservation::whereKey($id)->update(['status' => ReservationStatus::COMPLETED]);
    }

    public function store(array $data, int $projectId): Reservation
    {
        $reservedAt = DateUtil::nullableCarbon($data['reserved_at_date'].' '.$data['reserved_at_time']);

        return Reservation::create([
            'project_id' => $projectId,
            'reserved_at' => $reservedAt,
            'number_of_people' => $data['number_of_people'],
            'notes' => $data['notes'] ?? null,
            'status' => ReservationStatus::CONFIRMED,
        ]);
    }

    public function update(int $id, array $data): Reservation
    {
        $reservation = Reservation::findOrFail($id);

        $reservedAt = DateUtil::nullableCarbon($data['reserved_at_date'].' '.$data['reserved_at_time']);

        $reservation->update([
            'reserved_at' => $reservedAt,
            'number_of_people' => $data['number_of_people'],
            'notes' => $data['notes'] ?? null,
        ]);

        return $reservation->fresh();
    }

    public function destroy(int $id): void
    {
        Reservation::findOrFail($id)->delete();
    }
}
