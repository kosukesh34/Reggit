<?php

namespace App\Services\Admin;

use App\Domain\Reservation\Repositories\ReservationRepository;
use App\Domain\Settings\Repositories\ReservationSettings;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\ReservationCourse;
use App\Util\AdminContext;
use App\Util\DateUtil;
use InvalidArgumentException;

class ReservationAdminService
{
    public function __construct(
        private ReservationRepository $reservationRepository,
        private ReservationSettings $shopSettingRepository,
    ) {}

    public function getReservationIndexPayload(?string $status, ?string $selectedDate, int $projectId, int $perPage = 20): array
    {
        $query = Reservation::with('user.member')->orderByDesc('reserved_at');
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
        $reservationCapacity = $this->shopSettingRepository->getReservationCapacityPerSlot($projectId);

        if ($selectedDate !== null && $selectedDate !== '') {
            $date = DateUtil::nullableCarbon($selectedDate);
            if ($date !== null) {
                $selectedDateLabel = $date->format('Y年m月d日');
                $dateStatusCounts = $this->getStatusCounts($selectedDate);
                $timeSlots = $this->shopSettingRepository->getReservationTimeSlots($projectId);
                foreach ($timeSlots as $slot) {
                    $slotStart = DateUtil::nullableCarbon($date->format('Y-m-d').' '.$slot);
                    if ($slotStart === null) {
                        continue;
                    }
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
        return Reservation::with('user.member')->findOrFail($id);
    }

    public function updateStatus(int $id, string $status): void
    {
        $projectId = AdminContext::requireCurrentProjectId();
        $aggregate = $this->reservationRepository->findById($id, $projectId);
        if ($aggregate === null) {
            return;
        }

        $aggregate->applyAdminStatus($status);
        $this->reservationRepository->save($aggregate);
    }

    public function complete(int $id): void
    {
        $projectId = AdminContext::requireCurrentProjectId();
        $aggregate = $this->reservationRepository->findById($id, $projectId);
        if ($aggregate === null) {
            return;
        }

        $aggregate->complete();
        $this->reservationRepository->save($aggregate);
    }

    public function store(array $data, int $projectId): Reservation
    {
        $reservedAt = DateUtil::nullableCarbon($data['reserved_at_date'].' '.$data['reserved_at_time']);
        if ($reservedAt === null) {
            throw new InvalidArgumentException('予約日時が不正です');
        }

        $course = $this->resolveCourse($projectId, $data['reservation_course_id'] ?? null);

        return Reservation::create([
            'project_id' => $projectId,
            'reserved_at' => $reservedAt,
            'number_of_people' => $data['number_of_people'],
            'reservation_course_id' => $course?->id,
            'course_name' => $course?->name,
            'course_price' => $course?->price,
            'notes' => $data['notes'] ?? null,
            'status' => ReservationStatus::CONFIRMED,
        ]);
    }

    public function update(int $id, array $data): Reservation
    {
        $reservation = Reservation::findOrFail($id);

        $reservedAt = DateUtil::nullableCarbon($data['reserved_at_date'].' '.$data['reserved_at_time']);
        if ($reservedAt === null) {
            throw new InvalidArgumentException('予約日時が不正です');
        }

        $attributes = [
            'reserved_at' => $reservedAt,
            'number_of_people' => $data['number_of_people'],
            'notes' => $data['notes'] ?? null,
        ];
        if (array_key_exists('reservation_course_id', $data)) {
            $course = $this->resolveCourse((int) $reservation->project_id, $data['reservation_course_id']);
            $attributes['reservation_course_id'] = $course?->id;
            $attributes['course_name'] = $course?->name;
            $attributes['course_price'] = $course?->price;
        }
        $reservation->update($attributes);

        return $reservation->fresh();
    }

    public function destroy(int $id): void
    {
        Reservation::findOrFail($id)->delete();
    }

    private function resolveCourse(int $projectId, int|string|null $id): ?ReservationCourse
    {
        if ($id === null || $id === '') {
            return null;
        }

        return ReservationCourse::where('project_id', $projectId)->where('is_active', true)->findOrFail((int) $id);
    }
}
