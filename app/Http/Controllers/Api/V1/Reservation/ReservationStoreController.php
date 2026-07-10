<?php

namespace App\Http\Controllers\Api\V1\Reservation;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Services\UserService;
use App\UseCases\Reservation\Data\ReservationStoreInput;
use App\UseCases\Reservation\ReservationStoreUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReservationStoreController extends BaseApiController
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
            return $this->error('バリデーションエラー', 422, [], $e->errors());
        }

        try {
            $result = $this->storeUseCase->handle(new ReservationStoreInput(
                $this->userService->getOrCreateUser($request),
                $validated
            ));
            if ($result->requiresMembership) {
                return $this->error('会員登録が必要です', 403, ['requires_membership' => true]);
            }

            if ($result->availabilityError !== null) {
                return $this->error($result->availabilityError, 400);
            }

            return $this->success(['reservation' => $result->reservation], '予約が完了しました', 201);
        } catch (\Exception $e) {
            LogHelper::error('[Reservation] store failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);

            return $this->error('予約に失敗しました', 400);
        }
    }
}
