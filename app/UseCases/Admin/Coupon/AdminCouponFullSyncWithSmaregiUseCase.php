<?php

namespace App\UseCases\Admin\Coupon;

use App\Services\Smaregi\SmaregiCouponImportSyncService;

class AdminCouponFullSyncWithSmaregiUseCase
{
    public function __construct(
        private SmaregiCouponImportSyncService $smaregiCouponImportSyncService,
    ) {}

    /**
     * @return array{created: int, updated: int, pushed: int, failed: int, errors: list<string>}
     */
    public function handle(int $projectId): array
    {
        return $this->smaregiCouponImportSyncService->fullSync($projectId);
    }
}
