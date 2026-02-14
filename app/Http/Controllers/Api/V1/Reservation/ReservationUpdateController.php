<?php

namespace App\Http\Controllers\Api\V1\Reservation;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\UseCases\Reservation\ReservationUpdateUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class ReservationUpdateController extends BaseApiController
{
    public function __construct(
        private ReservationUpdateUseCase $updateUseCase
    ) {
    }

    public function __invoke(Request $request, int $id)
    {
        try {
            $validated = $request->validate([
                'reserved_at' => 'required|date|after:now',
                'number_of_people' => 'required|integer|min:1|max:10',
                'notes' => 'nullable|string|max:500',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('バリデーションエラー', 422, [], $e->errors());
        }

        try {
            $result = $this->updateUseCase->handle($request, $id, $validated);
            if ($result->requiresMembership) {
                return $this->error('会員登録が必要です', 403, ['requires_membership' => true]);
            }

            if ($result->availabilityError !== null) {
                return $this->error($result->availabilityError, 400);
            }

            return $this->success(['reservation' => $result->reservation], '予約を更新しました');
        } catch (\Exception $e) {
            LogHelper::error('[Reservation] update failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);
            return $this->error($e->getMessage(), 400);
        }
    }
}
