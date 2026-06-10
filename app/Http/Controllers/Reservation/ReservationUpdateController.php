<?php

namespace App\Http\Controllers\Reservation;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\UseCases\Reservation\Data\ReservationUpdateInput;
use App\UseCases\Reservation\ReservationUpdateUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReservationUpdateController extends Controller
{
    public function __construct(
        private ReservationUpdateUseCase $updateUseCase,
        private UserService $userService,
    ) {}

    public function __invoke(Request $request, int $id)
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
            $user = $this->userService->getOrCreateUser($request);
            $result = $this->updateUseCase->handle(new ReservationUpdateInput($user, $id, $validated));
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
                'message' => '予約を更新しました',
                'reservation' => $result->reservation,
            ]);
        } catch (\Exception $e) {
            LogHelper::error('[Reservation] update failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
