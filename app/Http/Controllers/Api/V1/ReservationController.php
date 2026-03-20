<?php

namespace App\Http\Controllers\Api\V1;

use App\UseCases\Reservation\ReservationAvailableDatesUseCase;
use App\UseCases\Reservation\ReservationCancelUseCase;
use App\UseCases\Reservation\ReservationIndexUseCase;
use App\UseCases\Reservation\ReservationStoreUseCase;
use App\UseCases\Reservation\ReservationUpdateUseCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * 予約 API（レガシー互換）。責務は UseCase に委譲する。
 */
class ReservationController extends BaseApiController
{
    private ReservationIndexUseCase $indexUseCase;

    private ReservationAvailableDatesUseCase $availableDatesUseCase;

    private ReservationStoreUseCase $storeUseCase;

    private ReservationUpdateUseCase $updateUseCase;

    private ReservationCancelUseCase $cancelUseCase;

    public function __construct(
        ReservationIndexUseCase $indexUseCase,
        ReservationAvailableDatesUseCase $availableDatesUseCase,
        ReservationStoreUseCase $storeUseCase,
        ReservationUpdateUseCase $updateUseCase,
        ReservationCancelUseCase $cancelUseCase
    ) {
        $this->indexUseCase = $indexUseCase;
        $this->availableDatesUseCase = $availableDatesUseCase;
        $this->storeUseCase = $storeUseCase;
        $this->updateUseCase = $updateUseCase;
        $this->cancelUseCase = $cancelUseCase;
    }

    public function index(Request $request)
    {
        try {
            $result = $this->indexUseCase->handle($request);
            if ($result->requiresMembership) {
                return $this->error('会員登録が必要です', 403, ['requires_membership' => true]);
            }

            $reservations = $result->reservations->map(fn ($r) => $r->toArray())->all();

            return $this->success(['reservations' => $reservations]);
        } catch (\Exception $e) {
            Log::error('Failed to get reservations: '.$e->getMessage());

            return $this->error('予約の取得に失敗しました', 500);
        }
    }

    public function getAvailableDates(Request $request)
    {
        try {
            $excludeReservationId = $request->query('exclude_reservation_id');
            $excludeId = filter_var($excludeReservationId, FILTER_VALIDATE_INT) !== false
                ? (int) $excludeReservationId
                : null;

            $result = $this->availableDatesUseCase->handle($excludeId);

            return $this->success([
                'available_dates' => $result->availableDates,
                'start_date' => $result->startDate,
                'end_date' => $result->endDate,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get available dates: '.$e->getMessage());

            return $this->error('利用可能日の取得に失敗しました', 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'reserved_at' => 'required|date|after:now',
                'number_of_people' => 'required|integer|min:1|max:10',
                'notes' => 'nullable|string|max:500',
            ]);
        } catch (ValidationException $e) {
            return $this->error('バリデーションエラー', 422, $e->errors());
        }

        try {
            $output = $this->storeUseCase->handle($request, $validated);
            if ($output->requiresMembership) {
                return $this->error('会員登録が必要です', 403, ['requires_membership' => true]);
            }
            if ($output->availabilityError !== null) {
                return $this->error($output->availabilityError, 400);
            }

            return $this->success(
                ['reservation' => $output->reservation->toArray()],
                '予約が完了しました',
                201
            );
        } catch (\Exception $e) {
            Log::error('Reservation creation failed: '.$e->getMessage());

            return $this->error('予約に失敗しました', 400);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $validated = $request->validate([
                'reserved_at' => 'required|date|after:now',
                'number_of_people' => 'required|integer|min:1|max:10',
                'notes' => 'nullable|string|max:500',
            ]);
        } catch (ValidationException $e) {
            return $this->error('バリデーションエラー', 422, $e->errors());
        }

        try {
            $output = $this->updateUseCase->handle($request, $id, $validated);
            if ($output->requiresMembership) {
                return $this->error('会員登録が必要です', 403, ['requires_membership' => true]);
            }
            if ($output->availabilityError !== null) {
                return $this->error($output->availabilityError, 400);
            }

            return $this->success(
                ['reservation' => $output->reservation->toArray()],
                '予約を更新しました'
            );
        } catch (\Exception $e) {
            Log::error('Reservation update failed: '.$e->getMessage());

            return $this->error($e->getMessage(), 400);
        }
    }

    public function cancel(Request $request, int $id)
    {
        try {
            $output = $this->cancelUseCase->handle($request, $id);
            if ($output->requiresMembership) {
                return $this->error('会員登録が必要です', 403, ['requires_membership' => true]);
            }

            return $this->success(
                ['reservation' => $output->reservation->toArray()],
                '予約をキャンセルしました'
            );
        } catch (\Exception $e) {
            Log::error('Reservation cancellation failed: '.$e->getMessage());

            return $this->error($e->getMessage(), 400);
        }
    }
}
