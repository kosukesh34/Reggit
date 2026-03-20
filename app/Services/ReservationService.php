<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\User;
use App\Util\LogHelper;
use Carbon\Carbon;

class ReservationService
{
    public function createReservation(User $user, array $data): Reservation
    {
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'reserved_at' => $data['reserved_at'],
            'number_of_people' => $data['number_of_people'] ?? 1,
            'status' => ReservationStatus::PENDING,
            'notes' => $data['notes'] ?? null,
        ]);

        LogHelper::info('[Reservation] created', [
            'reservation_id' => $reservation->id,
            'user_id' => $user->id,
        ]);

        return $reservation;
    }

    public function cancelReservation(int $reservationId, User $user): Reservation
    {
        $reservation = Reservation::where('id', $reservationId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($reservation->canCancel() === false) {
            throw new \Exception('この予約はキャンセルできません');
        }

        $reservation->update(['status' => ReservationStatus::CANCELLED]);

        LogHelper::info('[Reservation] cancelled', [
            'reservation_id' => $reservation->id,
            'user_id' => $user->id,
        ]);

        return $reservation;
    }

    public function updateReservation(int $reservationId, User $user, array $data): Reservation
    {
        $reservation = Reservation::where('id', $reservationId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($reservation->canEdit() === false) {
            throw new \Exception('この予約は編集できません');
        }

        $reservation->update([
            'reserved_at' => $data['reserved_at'],
            'number_of_people' => $data['number_of_people'] ?? $reservation->number_of_people,
            'notes' => $data['notes'] ?? null,
        ]);

        LogHelper::info('[Reservation] updated', [
            'reservation_id' => $reservation->id,
            'user_id' => $user->id,
        ]);

        return $reservation;
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
