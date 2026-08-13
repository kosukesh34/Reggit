<?php

namespace App\Services;

use App\Domain\Reservation\Repositories\ReservationRepository;
use App\Domain\Reservation\ValueObjects\PendingReservation;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\User;
use App\Util\AdminContext;
use App\Util\DateUtil;
use App\Util\LogHelper;

class ReservationService
{
    public function __construct(
        private ReservationRepository $reservationRepository,
    ) {}

    public function createReservation(User $user, array $data): Reservation
    {
        $projectId = (int) (AdminContext::resolveProjectId($user->project_id !== null ? (int) $user->project_id : null));
        $reservedAt = DateUtil::nullableCarbon($data['reserved_at']);
        if ($reservedAt === null) {
            throw new \InvalidArgumentException('予約日時が不正です');
        }

        $reservationId = $this->reservationRepository->persistPending(new PendingReservation(
            $projectId,
            (int) $user->id,
            $reservedAt,
            (int) ($data['number_of_people'] ?? 1),
            $data['notes'] ?? null,
        ));

        LogHelper::info('[Reservation] created', [
            'reservation_id' => $reservationId,
            'user_id' => $user->id,
        ]);

        return Reservation::findOrFail($reservationId);
    }

    public function cancelReservation(int $reservationId, User $user): Reservation
    {
        $projectId = (int) (AdminContext::resolveProjectId($user->project_id !== null ? (int) $user->project_id : null));
        $aggregate = $this->reservationRepository->findByIdAndUserIdOrFail(
            $reservationId,
            (int) $user->id,
            $projectId,
        );
        $aggregate->cancel();
        $this->reservationRepository->save($aggregate);

        LogHelper::info('[Reservation] cancelled', [
            'reservation_id' => $aggregate->id()->value(),
            'user_id' => $user->id,
            'project_id' => $projectId,
        ]);

        return Reservation::findOrFail($aggregate->id()->value());
    }

    public function updateReservation(int $reservationId, User $user, array $data): Reservation
    {
        $projectId = (int) (AdminContext::resolveProjectId($user->project_id !== null ? (int) $user->project_id : null));
        $aggregate = $this->reservationRepository->findByIdAndUserIdOrFail(
            $reservationId,
            (int) $user->id,
            $projectId,
        );

        $reservedAt = DateUtil::nullableCarbon($data['reserved_at']);
        if ($reservedAt === null) {
            throw new \InvalidArgumentException('予約日時が不正です');
        }

        $aggregate->updateDetails(
            $reservedAt,
            (int) ($data['number_of_people'] ?? $aggregate->numberOfPeople()),
            array_key_exists('notes', $data) ? $data['notes'] : $aggregate->notes(),
        );
        $this->reservationRepository->save($aggregate);

        LogHelper::info('[Reservation] updated', [
            'reservation_id' => $aggregate->id()->value(),
            'user_id' => $user->id,
        ]);

        return Reservation::findOrFail($aggregate->id()->value());
    }

    public function getUserReservations(User $user): array
    {
        try {
            $reservations = $user->reservations()
                ->orderBy('reserved_at', 'desc')
                ->get();

            return $reservations->map(function ($reservation) {
                return [
                    'id' => $reservation->id,
                    'reservation_number' => $reservation->reservation_number ?? '',
                    'reserved_at' => DateUtil::nullableCarbon($reservation->reserved_at)?->format('c'),
                    'number_of_people' => $reservation->number_of_people ?? 1,
                    'status' => $reservation->status ?? ReservationStatus::PENDING,
                    'notes' => $reservation->notes ?? null,
                ];
            })->toArray();
        } catch (\Exception $e) {
            LogHelper::error('[Reservation] get user reservations failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
