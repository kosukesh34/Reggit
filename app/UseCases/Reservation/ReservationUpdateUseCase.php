<?php

namespace App\UseCases\Reservation;

use App\Models\Store;
use App\Services\ReservationService;
use App\UseCases\Reservation\Data\ReservationActionOutput;
use App\UseCases\Reservation\Data\ReservationUpdateInput;
use App\Util\AdminContext;
use App\Util\DateUtil;
use App\Util\StoreContext;
use Illuminate\Support\Facades\DB;

class ReservationUpdateUseCase
{
    public function __construct(
        private ReservationService $reservationService,
        private ReservationAvailabilityService $availabilityService,
    ) {}

    public function handle(ReservationUpdateInput $input): ReservationActionOutput
    {
        if ($input->user->member === null) {
            return new ReservationActionOutput(true, null, null);
        }

        $reservationPayload = $input->reservationPayload;
        $reservedAt = DateUtil::nullableCarbon($reservationPayload['reserved_at'])
            ?->setTimezone(config('app.timezone'));
        if ($reservedAt === null) {
            return new ReservationActionOutput(false, null, '予約日時が不正です');
        }

        return DB::transaction(function () use ($input, $reservedAt, $reservationPayload): ReservationActionOutput {
            $projectId = AdminContext::requireCurrentProjectId();
            $storeId = app(StoreContext::class)->resolveForWrite($projectId);
            // An existing store row serializes capacity checks, including empty slots.
            Store::where('project_id', $projectId)->whereKey($storeId)->lockForUpdate()->firstOrFail();

            $availabilityError = $this->availabilityService->getAvailabilityError($reservedAt, $input->reservationId);
            if ($availabilityError !== null) {
                return new ReservationActionOutput(false, null, $availabilityError);
            }

            $reservationPayload['reserved_at'] = $reservedAt->format('Y-m-d H:i:s');
            $reservation = $this->reservationService->updateReservation($input->reservationId, $input->user, $reservationPayload);

            return new ReservationActionOutput(false, $reservation, null);
        }, 3);
    }
}
