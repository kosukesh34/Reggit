<?php

namespace App\UseCases\Reservation;

use App\Models\User;
use App\Services\ReservationService;
use App\UseCases\Reservation\Data\ReservationActionOutput;
use App\Util\AdminContext;
use Illuminate\Support\Facades\DB;

class ReservationCancelUseCase
{
    public function __construct(
        private ReservationService $reservationService,
    ) {}

    public function handle(User $user, int $reservationId): ReservationActionOutput
    {
        $projectId = AdminContext::requireCurrentProjectId();

        return DB::transaction(function () use ($user, $reservationId, $projectId): ReservationActionOutput {
            $owner = User::query()->where('project_id', $projectId)->whereKey($user->id)->lockForUpdate()->first();
            if ($owner === null
                || (int) $owner->authentication_version !== (int) $user->authentication_version
                || $owner->member()->where('project_id', $projectId)->lockForUpdate()->first() === null) {
                return new ReservationActionOutput(true, null, null);
            }

            $reservation = $this->reservationService->cancelReservation($reservationId, $owner);

            return new ReservationActionOutput(false, $reservation, null);
        }, 3);
    }
}
