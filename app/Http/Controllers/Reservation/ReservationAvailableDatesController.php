<?php

namespace App\Http\Controllers\Reservation;

use App\Http\Controllers\Controller;
use App\UseCases\Reservation\ReservationAvailableDatesUseCase;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class ReservationAvailableDatesController extends Controller
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

            $result = $this->availableDatesUseCase->handle($excludeReservationId);

            return response()->json([
                'available_dates' => $result->availableDates,
                'start_date' => $result->startDate,
                'end_date' => $result->endDate,
            ]);
        } catch (\Exception $e) {
            LogHelper::error('[Reservation] available dates failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => '利用可能日の取得に失敗しました'], 500);
        }
    }
}
