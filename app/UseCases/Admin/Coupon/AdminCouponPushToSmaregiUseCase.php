<?php

namespace App\UseCases\Admin\Coupon;

use App\Services\Smaregi\SmaregiCouponImportSyncService;

class AdminCouponPushToSmaregiUseCase
{
    public function __construct(
        private SmaregiCouponImportSyncService $smaregiCouponImportSyncService,
    ) {}

    /**
     * @return array{pushed: int, failed: int, errors: list<string>}
     */
    public function handle(int $projectId): array
    {
        return $this->smaregiCouponImportSyncService->pushUnlinkedToSmaregi($projectId);
    }
}
