<?php

namespace App\UseCases\Admin\Coupon;

use App\Services\Smaregi\SmaregiCouponImportSyncService;

class AdminCouponSyncFromSmaregiUseCase
{
    public function __construct(
        private SmaregiCouponImportSyncService $smaregiCouponImportSyncService,
    ) {}

    /**
     * @return array{created: int, updated: int, failed: int, errors: list<string>}
     */
    public function handle(int $projectId): array
    {
        return $this->smaregiCouponImportSyncService->syncFromSmaregi($projectId);
    }
}
