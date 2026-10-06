<?php

namespace App\Infrastructure\Persistence\Eloquent\Reservation;

use App\Domain\Reservation\Aggregates\Reservation as ReservationAggregate;
use App\Domain\Reservation\Repositories\ReservationRepository;
use App\Domain\Reservation\ValueObjects\PendingReservation;
use App\Enums\ReservationStatus;
use App\Models\Reservation as ReservationModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EloquentReservationRepository implements ReservationRepository
{
    public function findById(int $reservationId, int $projectId): ?ReservationAggregate
    {
        $model = ReservationModel::query()
            ->whereKey($reservationId)
            ->where('project_id', $projectId)
            ->first();
        if ($model === null) {
            return null;
        }

        return ReservationMapper::toDomain($model);
    }

    public function findByIdAndUserId(int $reservationId, int $userId, int $projectId): ?ReservationAggregate
    {
        $model = ReservationModel::query()
            ->where('id', $reservationId)
            ->where('user_id', $userId)
            ->where('project_id', $projectId)
            ->first();

        if ($model === null) {
            return null;
        }

        return ReservationMapper::toDomain($model);
    }

    public function findByIdAndUserIdOrFail(int $reservationId, int $userId, int $projectId): ReservationAggregate
    {
        $aggregate = $this->findByIdAndUserId($reservationId, $userId, $projectId);
        if ($aggregate === null) {
            throw (new ModelNotFoundException)->setModel(ReservationModel::class, [$reservationId]);
        }

        return $aggregate;
    }

    public function persistPending(PendingReservation $pending): int
    {
        $model = ReservationModel::create([
            'project_id' => $pending->projectId,
            'user_id' => $pending->userId,
            'reserved_at' => $pending->reservedAt,
            'number_of_people' => $pending->numberOfPeople,
            'status' => ReservationStatus::PENDING,
            'notes' => $pending->notes,
        ]);

        return (int) $model->id;
    }

    public function getActiveSlotCounts(
        int $projectId,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        ?int $excludeReservationId,
    ): array {
        $query = ReservationModel::query()
            ->where('project_id', $projectId)
            ->whereBetween('reserved_at', [$start, $end])
            ->whereIn('status', [ReservationStatus::PENDING, ReservationStatus::CONFIRMED]);
        $reservations = $query->get(['id', 'reserved_at']);

        $slotCounts = [];
        foreach ($reservations as $reservation) {
            if ($excludeReservationId !== null && (int) $reservation->id === $excludeReservationId) {
                continue;
            }
            $reservedAt = $reservation->reserved_at;
            $dateKey = $reservedAt->format('Y-m-d');
            $timeKey = $reservedAt->format('H:i');
            if (! isset($slotCounts[$dateKey])) {
                $slotCounts[$dateKey] = [];
            }
            if (! isset($slotCounts[$dateKey][$timeKey])) {
                $slotCounts[$dateKey][$timeKey] = 0;
            }
            $slotCounts[$dateKey][$timeKey] += 1;
        }

        return $slotCounts;
    }

    public function countActiveInSlot(
        int $projectId,
        \DateTimeInterface $slotStart,
        \DateTimeInterface $slotEnd,
        ?int $excludeReservationId,
    ): int {
        $query = ReservationModel::query()
            ->where('project_id', $projectId)
            ->whereBetween('reserved_at', [$slotStart, $slotEnd])
            ->whereIn('status', [ReservationStatus::PENDING, ReservationStatus::CONFIRMED]);
        if ($excludeReservationId !== null) {
            $query->where('id', '!=', $excludeReservationId);
        }

        // Use a current locking read even when the caller already has a transaction snapshot.
        return $query->lockForUpdate()->get(['id'])->count();
    }

    public function save(ReservationAggregate $reservation): void
    {
        $model = ReservationModel::query()
            ->whereKey($reservation->id()->value())
            ->where('project_id', $reservation->projectId())
            ->first();
        if ($model === null) {
            throw new \RuntimeException('Reservation not found for save: '.$reservation->id()->value());
        }

        $model->update([
            'status' => $reservation->status(),
            'reserved_at' => $reservation->reservedAt(),
            'number_of_people' => $reservation->numberOfPeople(),
            'notes' => $reservation->notes(),
        ]);
    }
}
