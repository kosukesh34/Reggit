<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\UserService;
use App\UseCases\Coupon\CouponMyCouponsUseCase;
use App\UseCases\Coupon\CouponUseMemberCouponUseCase;
use App\Util\LogHelper;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CouponController extends BaseApiController
{
    public function __construct(
        private CouponMyCouponsUseCase $myCouponsUseCase,
        private CouponUseMemberCouponUseCase $useMemberCouponUseCase,
        private UserService $userService,
    ) {}

    public function myCoupons(Request $request)
    {
        try {
            $result = $this->myCouponsUseCase->handle($this->userService->getOrCreateUser($request));
            if ($result->requiresMembership) {
                return $this->success([
                    'coupons' => [],
                    'message' => '会員登録が必要です',
                ]);
            }

            return $this->success([
                'coupons' => $result->couponsToArray(),
                'default_image_url' => $result->defaultImageUrl,
            ]);
        } catch (\Exception $e) {
            LogHelper::error('[Coupon] my coupons failed', [
                'error' => $e->getMessage(),
            ]);

            return $this->error('クーポンの取得に失敗しました', 500);
        }
    }

    public function use(Request $request)
    {
        $id = (int) $request->route('id');
        try {
            $result = $this->useMemberCouponUseCase->handle($this->userService->getOrCreateUser($request), $id);

            return $this->success($result->toArray(), 'クーポンを使用しました');
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? 'クーポンを使用できません';

            return $this->error($message, 422, [], $e->errors());
        } catch (\Exception $e) {
            LogHelper::error('[Coupon] use failed', [
                'member_coupon_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('クーポンの使用に失敗しました', 500);
        }
    }
}
