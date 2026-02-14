<?php

namespace App\UseCases\Reservation;

use App\Services\ReservationService;
use App\Services\UserService;
use App\UseCases\Reservation\Dto\ReservationActionResultDto;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReservationUpdateUseCase
{
    public function __construct(
        private ReservationService $reservationService,
        private ReservationAvailabilityService $availabilityService,
        private UserService $userService
    ) {
    }

    /** @param array<string, mixed> $validated */
    public function handle(Request $request, int $reservationId, array $validated): ReservationActionResultDto
    {
        $user = $this->userService->getOrCreateUser($request);
        if ($user->member === null) {
            return new ReservationActionResultDto(true, null, null);
        }

        $reservedAt = Carbon::parse($validated['reserved_at']);
        $availabilityError = $this->availabilityService->getAvailabilityError($reservedAt, $reservationId);
        if ($availabilityError !== null) {
            return new ReservationActionResultDto(false, null, $availabilityError);
        }

        $validated['reserved_at'] = $reservedAt->format('Y-m-d H:i:s');
        $reservation = $this->reservationService->updateReservation($reservationId, $user, $validated);

        return new ReservationActionResultDto(false, $reservation, null);
    }
}
