<?php

namespace App\UseCases\Admin\Settings;

use App\Services\Smaregi\SmaregiProductSyncService;

class AdminFullSyncProductsWithSmaregiUseCase
{
    public function __construct(
        private SmaregiProductSyncService $smaregiProductSyncService,
    ) {}

    /**
     * @return array{created: int, updated: int, pushed_to_smaregi: int, deleted_from_smaregi: int, stock_synced: int, failed: int, errors: list<string>}
     */
    public function handle(int $projectId): array
    {
        return $this->smaregiProductSyncService->fullSync($projectId);
    }
}
