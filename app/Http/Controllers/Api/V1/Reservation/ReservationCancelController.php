<?php

namespace App\Http\Controllers\Api\V1\Reservation;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Services\UserService;
use App\UseCases\Reservation\ReservationCancelUseCase;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class ReservationCancelController extends BaseApiController
{
    public function __construct(
        private ReservationCancelUseCase $cancelUseCase,
        private UserService $userService,
    ) {}

    public function __invoke(Request $request, int $id)
    {
        try {
            $result = $this->cancelUseCase->handle($this->userService->getOrCreateUser($request), $id);
            if ($result->requiresMembership) {
                return $this->error('会員登録が必要です', 403, ['requires_membership' => true]);
            }

            return $this->success(['reservation' => $result->reservation], '予約をキャンセルしました');
        } catch (\Exception $e) {
            LogHelper::error('[Reservation] cancel failed', [
                'error' => $e->getMessage(),
            ]);

            return $this->error($e->getMessage(), 400);
        }
    }
}
