<?php

namespace App\UseCases\Reservation;

use App\Models\Store;
use App\Models\User;
use App\Services\ReservationService;
use App\UseCases\Reservation\Data\ReservationActionOutput;
use App\Util\AdminContext;
use App\Util\DateUtil;
use App\Util\StoreContext;
use Illuminate\Support\Facades\DB;

class ReservationCapacityLockedWriter
{
    public function __construct(
        private ReservationService $reservationService,
        private ReservationAvailabilityService $availabilityService,
        private StoreContext $storeContext,
    ) {}

    /**
     * @param  array<string, mixed>  $reservationPayload
     */
    public function handle(User $user, array $reservationPayload, ?int $reservationId): ReservationActionOutput
    {
        $reservedAt = DateUtil::nullableCarbon($reservationPayload['reserved_at'])
            ?->setTimezone(config('app.timezone'));
        if ($reservedAt === null) {
            return new ReservationActionOutput(false, null, '予約日時が不正です');
        }

        return DB::transaction(function () use ($user, $reservedAt, $reservationPayload, $reservationId): ReservationActionOutput {
            $projectId = AdminContext::requireCurrentProjectId();
            $lockedUser = User::query()->where('project_id', $projectId)->whereKey($user->id)
                ->lockForUpdate()->first();
            if ($lockedUser === null
                || (int) $lockedUser->authentication_version !== (int) $user->authentication_version
                || $lockedUser->member()->where('project_id', $projectId)->lockForUpdate()->first() === null) {
                return new ReservationActionOutput(true, null, null);
            }
            $storeId = $this->storeContext->resolveForWrite($projectId);
            // An existing store row serializes capacity checks, including empty slots.
            Store::where('project_id', $projectId)->whereKey($storeId)->lockForUpdate()->firstOrFail();

            $availabilityError = $this->availabilityService->getAvailabilityError(
                $reservedAt,
                $reservationId,
                (int) ($reservationPayload['number_of_people'] ?? 1),
            );
            if ($availabilityError !== null) {
                return new ReservationActionOutput(false, null, $availabilityError);
            }

            $reservationPayload['reserved_at'] = $reservedAt->format('Y-m-d H:i:s');
            $reservation = $reservationId === null
                ? $this->reservationService->createReservation($lockedUser, $reservationPayload)
                : $this->reservationService->updateReservation($reservationId, $lockedUser, $reservationPayload);

            return new ReservationActionOutput(false, $reservation, null);
        }, 3);
    }
}
