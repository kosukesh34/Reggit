<?php

namespace App\UseCases\Admin\Settings;

use App\Services\Smaregi\SmaregiProductSyncService;

class AdminSyncProductsFromSmaregiUseCase
{
    public function __construct(
        private SmaregiProductSyncService $smaregiProductSyncService,
    ) {}

    /**
     * @return array{created: int, updated: int, failed: int, errors: list<string>}
     */
    public function handle(int $projectId): array
    {
        return $this->smaregiProductSyncService->sync($projectId);
    }
}
