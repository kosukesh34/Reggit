<?php

namespace App\Http\Controllers\Reservation;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\UseCases\Reservation\Data\ReservationStoreInput;
use App\UseCases\Reservation\ReservationStoreUseCase;
use App\Util\LogHelper;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReservationStoreController extends Controller
{
    public function __construct(
        private ReservationStoreUseCase $storeUseCase,
        private UserService $userService,
    ) {}

    public function __invoke(Request $request)
    {
        try {
            $validated = $request->validate([
                'reserved_at' => 'required|date|after:now',
                'number_of_people' => 'required|integer|min:1|max:10',
                'notes' => 'nullable|string|max:500',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'バリデーションエラー',
                'errors' => $e->errors(),
            ], 422);
        }

        try {
            $result = $this->storeUseCase->handle(new ReservationStoreInput(
                $this->userService->getOrCreateUser($request),
                $validated
            ));
            if ($result->requiresMembership) {
                return response()->json([
                    'error' => '会員登録が必要です',
                    'requires_membership' => true,
                ], 403);
            }

            if ($result->availabilityError !== null) {
                return response()->json(['error' => $result->availabilityError], 400);
            }

            return response()->json([
                'message' => '予約が完了しました',
                'reservation' => $result->reservation,
            ], 201);
        } catch (\Exception $e) {
            LogHelper::error('[Reservation] store failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => '予約に失敗しました'], 400);
        }
    }
}
