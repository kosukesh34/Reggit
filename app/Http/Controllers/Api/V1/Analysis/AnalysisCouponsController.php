<?php

namespace App\Http\Controllers\Api\V1\Analysis;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\UseCases\Admin\Analysis\AdminAnalysisCouponIndexUseCase;
use App\Util\AdminContext;
use Illuminate\Http\JsonResponse;

class AnalysisCouponsController extends BaseApiController
{
    public function __construct(private AdminAnalysisCouponIndexUseCase $coupons) {}

    public function __invoke(): JsonResponse
    {
        return $this->success([
            'coupons' => $this->coupons->handle(AdminContext::requireCurrentProjectId())
                ->map(fn ($coupon) => ['id' => $coupon->id, 'name' => $coupon->name])
                ->values()
                ->all(),
        ]);
    }
}
