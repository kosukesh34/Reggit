<?php

namespace App\Http\Controllers\Api\V1\Reservation;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\UseCases\Reservation\ReservationCancelUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class ReservationCancelController extends BaseApiController
{
    public function __construct(
        private ReservationCancelUseCase $cancelUseCase
    ) {
    }

    public function __invoke(Request $request, int $id)
    {
        try {
            $result = $this->cancelUseCase->handle($request, $id);
            if ($result->requiresMembership) {
                return $this->error('会員登録が必要です', 403, ['requires_membership' => true]);
            }

            return $this->success(['reservation' => $result->reservation], '予約をキャンセルしました');
        } catch (\Exception $e) {
            LogHelper::error('[Reservation] cancel failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);
            return $this->error($e->getMessage(), 400);
        }
    }
}
