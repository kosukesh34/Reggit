<?php

namespace App\Http\Controllers\Api\V1\Reservation;

use App\Domain\Reservation\ValueObjects\ReservationRules;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\UseCases\Reservation\ReservationAvailableDatesUseCase;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class ReservationAvailableDatesController extends BaseApiController
{
    public function __construct(
        private ReservationAvailableDatesUseCase $availableDatesUseCase
    ) {}

    public function __invoke(Request $request)
    {
        try {
            $excludeReservationId = $request->query('exclude_reservation_id');
            if ($excludeReservationId !== null) {
                $excludeReservationId = filter_var($excludeReservationId, FILTER_VALIDATE_INT) ?: null;
            }

            $numberOfPeople = filter_var($request->query('number_of_people', 1), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => ReservationRules::MAX_PARTY_SIZE_LIMIT]]) ?: 1;

            $result = $this->availableDatesUseCase->handle($excludeReservationId, $numberOfPeople);

            return $this->success([
                'available_dates' => $result->availableDates,
                'start_date' => $result->startDate,
                'end_date' => $result->endDate,
                'courses' => $result->courses,
                'max_party_size' => $result->maxPartySize,
                'change_deadline_hours' => $result->changeDeadlineHours,
            ]);
        } catch (\Exception $e) {
            LogHelper::error('[Reservation] available dates failed', [
                'error' => $e->getMessage(),
            ]);

            return $this->error('利用可能日の取得に失敗しました', 500);
        }
    }
}
