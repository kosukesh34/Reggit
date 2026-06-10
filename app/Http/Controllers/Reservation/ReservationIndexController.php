<?php

namespace App\Http\Controllers\Reservation;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\UseCases\Member\Data\MemberUserInput;
use App\UseCases\Reservation\ReservationIndexUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class ReservationIndexController extends Controller
{
    public function __construct(
        private ReservationIndexUseCase $indexUseCase,
        private UserService $userService,
    ) {}

    public function __invoke(Request $request)
    {
        try {
            $user = $this->userService->getOrCreateUser($request);
            $result = $this->indexUseCase->handle(new MemberUserInput($user));
            if ($result->requiresMembership) {
                return response()->json([
                    'error' => '会員登録が必要です',
                    'requires_membership' => true,
                ], 403);
            }

            return response()->json(['reservations' => $result->reservations]);
        } catch (\Exception $e) {
            LogHelper::error('[Reservation] index failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => '予約の取得に失敗しました'], 500);
        }
    }
}
