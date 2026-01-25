<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReservationService
{
    public function createReservation(User $user, array $data): Reservation
    {
        DB::beginTransaction();
        try {
            $reservation = Reservation::create([
                'user_id' => $user->id,
                'reserved_at' => $data['reserved_at'],
                'number_of_people' => $data['number_of_people'] ?? 1,
                'status' => ReservationStatus::PENDING,
                'notes' => $data['notes'] ?? null,
            ]);

            DB::commit();
            Log::info('Reservation created', [
                'reservation_id' => $reservation->id,
                'user_id' => $user->id,
            ]);

            return $reservation;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Reservation creation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function cancelReservation(int $reservationId, User $user): Reservation
    {
        $reservation = Reservation::where('id', $reservationId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if (!$reservation->canCancel()) {
            throw new \Exception('この予約はキャンセルできません');
        }

        $reservation->update(['status' => ReservationStatus::CANCELLED]);

        Log::info('Reservation cancelled', [
            'reservation_id' => $reservation->id,
            'user_id' => $user->id,
        ]);

        return $reservation;
    }

    public function getUserReservations(User $user): array
    {
        $reservations = $user->reservations()
            ->orderBy('reserved_at', 'desc')
            ->get();

        return $reservations->map(function ($reservation) {
            return [
                'id' => $reservation->id,
                'reservation_number' => $reservation->reservation_number,
                'reserved_at' => $reservation->reserved_at->toIso8601String(),
                'number_of_people' => $reservation->number_of_people,
                'status' => $reservation->status,
                'notes' => $reservation->notes,
            ];
        })->toArray();
    }
}

