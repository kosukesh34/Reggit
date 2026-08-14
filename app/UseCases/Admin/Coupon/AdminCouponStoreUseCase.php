<?php

namespace App\UseCases\Admin\Coupon;

use App\Models\Coupon;
use App\Services\Admin\CouponAdminService;

class AdminCouponStoreUseCase
{
    private CouponAdminService $couponAdminService;

    public function __construct(CouponAdminService $couponAdminService)
    {
        $this->couponAdminService = $couponAdminService;
    }

    /** @param array<string, mixed> $validated */
    public function handle(int $projectId, array $validated, bool $isQuickCreate = false): Coupon
    {
        if ($isQuickCreate) {
            $validated['is_active'] = true;
            $validated['use_display'] = $validated['use_display'] ?? Coupon::USE_DISPLAY_BARCODE;
        }
        // クーポン作成では配布条件を持たせない。配布はお知らせ配信から行う。
        $validated['issue_trigger'] = Coupon::ISSUE_TRIGGER_MANUAL;
        $validated['stamp_card_id'] = null;
        $validated['design_template'] = Coupon::DESIGN_CLASSIC;

        return $this->couponAdminService->create($projectId, $validated);
    }
}
