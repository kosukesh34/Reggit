<?php

namespace App\Http\Controllers\Api\V1\Reservation;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\UseCases\Reservation\ReservationIndexUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class ReservationIndexController extends BaseApiController
{
    public function __construct(
        private ReservationIndexUseCase $indexUseCase
    ) {
    }

    public function __invoke(Request $request)
    {
        try {
            $result = $this->indexUseCase->handle($request);
            if ($result->requiresMembership) {
                return $this->error('会員登録が必要です', 403, ['requires_membership' => true]);
            }

            return $this->success(['reservations' => $result->reservations]);
        } catch (\Exception $e) {
            LogHelper::error('[Reservation] index failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);
            return $this->error('予約の取得に失敗しました', 500);
        }
    }
}
