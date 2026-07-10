<?php

namespace App\UseCases\Admin\Coupon;

use App\Services\Admin\CouponAdminService;
use Illuminate\Http\UploadedFile;

class AdminCouponSaveImageUseCase
{
    public function __construct(
        private CouponAdminService $couponAdminService,
    ) {}

    public function handle(UploadedFile $imageFile, int $projectId): string
    {
        return $this->couponAdminService->saveCouponImage($imageFile, $projectId);
    }
}
