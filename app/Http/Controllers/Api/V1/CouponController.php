<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\UserService;
use App\UseCases\Coupon\CouponMyCouponsUseCase;
use App\UseCases\Member\Data\MemberUserInput;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class CouponController extends BaseApiController
{
    public function __construct(
        private CouponMyCouponsUseCase $myCouponsUseCase,
        private UserService $userService,
    ) {}

    public function myCoupons(Request $request)
    {
        try {
            $user = $this->userService->getOrCreateUser($request);
            $result = $this->myCouponsUseCase->handle(new MemberUserInput($user));
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
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);

            return $this->error('クーポンの取得に失敗しました', 500);
        }
    }
}
