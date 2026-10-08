<?php

namespace App\Infrastructure\Persistence\Eloquent\Reservation;

use App\Domain\Reservation\Aggregates\Reservation as ReservationAggregate;
use App\Domain\Reservation\Repositories\ReservationRepository;
use App\Domain\Reservation\ValueObjects\PendingReservation;
use App\Enums\ReservationStatus;
use App\Models\Reservation as ReservationModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EloquentReservationRepository implements ReservationRepository
{
    public function findByIdForUpdate(int $reservationId, int $projectId, ?int $userId = null): ?ReservationAggregate
    {
        $query = ReservationModel::query()->whereKey($reservationId)->where('project_id', $projectId);
        if ($userId !== null) {
            $query->where('user_id', $userId);
        }
        $model = $query->lockForUpdate()->first();

        return $model === null ? null : ReservationMapper::toDomain($model);
    }

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
            'store_id' => $pending->storeId,
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
        // @phpstan-ignore-next-line larastan.noUnnecessaryCollectionCall (Fetch rows to acquire locks before counting.)
        return $query->lockForUpdate()->get(['id'])->count();
    }

    public function save(ReservationAggregate $reservation): void
    {
        DB::transaction(function () use ($reservation): void {
            $model = ReservationModel::query()
                ->whereKey($reservation->id()->value())
                ->where('project_id', $reservation->projectId())
                ->lockForUpdate()->firstOrFail();
            if ((int) $model->version !== $reservation->version()) {
                throw ValidationException::withMessages(['reservation' => '予約が他の操作で変更されました。再読み込みしてください。']);
            }
            $model->update([
                'status' => $reservation->status(),
                'reserved_at' => $reservation->reservedAt(),
                'number_of_people' => $reservation->numberOfPeople(),
                'notes' => $reservation->notes(),
                'version' => $reservation->version() + 1,
            ]);
        });
    }
}
