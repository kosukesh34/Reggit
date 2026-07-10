<?php

namespace App\Services;

use App\Domain\Reservation\Repositories\ReservationRepository;
use App\Domain\Reservation\ValueObjects\PendingReservation;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\User;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Carbon\Carbon;

class ReservationService
{
    public function __construct(
        private ReservationRepository $reservationRepository,
    ) {}

    public function createReservation(User $user, array $data): Reservation
    {
        $projectId = (int) ($user->project_id ?? AdminContext::getCurrentProjectId() ?? 1);
        $reservedAt = $data['reserved_at'];
        if ($reservedAt instanceof \DateTimeInterface === false) {
            $reservedAt = Carbon::parse((string) $reservedAt);
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
        $projectId = (int) ($user->project_id ?? AdminContext::getCurrentProjectId() ?? 1);
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
        $projectId = (int) ($user->project_id ?? AdminContext::getCurrentProjectId() ?? 1);
        $aggregate = $this->reservationRepository->findByIdAndUserIdOrFail(
            $reservationId,
            (int) $user->id,
            $projectId,
        );

        $reservedAt = $data['reserved_at'];
        if ($reservedAt instanceof \DateTimeInterface === false) {
            $reservedAt = Carbon::parse((string) $reservedAt);
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
                $reservedAt = $reservation->reserved_at;
                if ($reservedAt === null) {
                    $reservedAtStr = null;
                } elseif ($reservedAt instanceof Carbon || $reservedAt instanceof \DateTime) {
                    $reservedAtStr = $reservedAt->format('c');
                } else {
                    $reservedAtStr = is_string($reservedAt) ? $reservedAt : (string) $reservedAt;
                }

                return [
                    'id' => $reservation->id,
                    'reservation_number' => $reservation->reservation_number ?? '',
                    'reserved_at' => $reservedAtStr,
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
