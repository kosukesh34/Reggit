<?php

namespace App\Http\Controllers\Api\V1;

use App\UseCases\Coupon\CouponMyCouponsUseCase;
use App\Util\AdminContext;
use Illuminate\Http\Request;
use App\Util\LogHelper;

class CouponController extends BaseApiController
{
    public function __construct(private CouponMyCouponsUseCase $myCouponsUseCase)
    {
    }

    public function myCoupons(Request $request)
    {
        try {
            $result = $this->myCouponsUseCase->handle($request);
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
