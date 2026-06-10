<?php

namespace App\Http\Controllers\Reservation;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\UseCases\Reservation\Data\ReservationCancelInput;
use App\UseCases\Reservation\ReservationCancelUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class ReservationCancelController extends Controller
{
    public function __construct(
        private ReservationCancelUseCase $cancelUseCase,
        private UserService $userService,
    ) {}

    public function __invoke(Request $request, int $id)
    {
        try {
            $user = $this->userService->getOrCreateUser($request);
            $result = $this->cancelUseCase->handle(new ReservationCancelInput($user, $id));
            if ($result->requiresMembership) {
                return response()->json([
                    'error' => '会員登録が必要です',
                    'requires_membership' => true,
                ], 403);
            }

            return response()->json([
                'message' => '予約をキャンセルしました',
                'reservation' => $result->reservation,
            ]);
        } catch (\Exception $e) {
            LogHelper::error('[Reservation] cancel failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
