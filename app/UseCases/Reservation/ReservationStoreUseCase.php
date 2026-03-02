<?php

namespace App\UseCases\Reservation;

use App\Services\ReservationService;
use App\Services\UserService;
use App\UseCases\Reservation\Data\ReservationActionOutput;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReservationStoreUseCase
{
    public function __construct(
        private ReservationService $reservationService,
        private ReservationAvailabilityService $availabilityService,
        private UserService $userService
    ) {}

    /** @param array<string, string|int|float|bool|array|null> $validated 検証済み予約データ（reserved_at, party_size 等） */
    public function handle(Request $request, array $validated): ReservationActionOutput
    {
        $user = $this->userService->getOrCreateUser($request);
        if ($user->member === null) {
            return new ReservationActionOutput(true, null, null);
        }

        $reservedAt = Carbon::parse($validated['reserved_at']);
        $availabilityError = $this->availabilityService->getAvailabilityError($reservedAt, null);
        if ($availabilityError !== null) {
            return new ReservationActionOutput(false, null, $availabilityError);
        }

        $validated['reserved_at'] = $reservedAt->format('Y-m-d H:i:s');
        $reservation = $this->reservationService->createReservation($user, $validated);

        return new ReservationActionOutput(false, $reservation, null);
    }
}
