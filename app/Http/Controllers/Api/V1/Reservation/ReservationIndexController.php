<?php

namespace App\Http\Controllers\Api\V1\Reservation;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Services\UserService;
use App\UseCases\Reservation\ReservationIndexUseCase;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class ReservationIndexController extends BaseApiController
{
    public function __construct(
        private ReservationIndexUseCase $indexUseCase,
        private UserService $userService,
    ) {}

    public function __invoke(Request $request)
    {
        try {
            $result = $this->indexUseCase->handle($this->userService->getOrCreateUser($request));
            if ($result->requiresMembership) {
                return $this->error('会員登録が必要です', 403, ['requires_membership' => true]);
            }

            return $this->success(['reservations' => $result->reservations]);
        } catch (\Exception $e) {
            LogHelper::error('[Reservation] index failed', [
                'error' => $e->getMessage(),
            ]);

            return $this->error('予約の取得に失敗しました', 500);
        }
    }
}
