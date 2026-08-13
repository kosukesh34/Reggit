<?php

namespace App\Http\Controllers\Api\V1\Reservation;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Services\UserService;
use App\UseCases\Reservation\Data\ReservationUpdateInput;
use App\UseCases\Reservation\ReservationUpdateUseCase;
use App\Util\LogHelper;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReservationUpdateController extends BaseApiController
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
            return $this->error('バリデーションエラー', 422, [], $e->errors());
        }

        try {
            $result = $this->updateUseCase->handle(new ReservationUpdateInput(
                $this->userService->getOrCreateUser($request),
                $id,
                $validated
            ));
            if ($result->requiresMembership) {
                return $this->error('会員登録が必要です', 403, ['requires_membership' => true]);
            }

            if ($result->availabilityError !== null) {
                return $this->error($result->availabilityError, 400);
            }

            return $this->success(['reservation' => $result->reservation], '予約を更新しました');
        } catch (\Exception $e) {
            LogHelper::error('[Reservation] update failed', [
                'error' => $e->getMessage(),
            ]);

            return $this->error($e->getMessage(), 400);
        }
    }
}
