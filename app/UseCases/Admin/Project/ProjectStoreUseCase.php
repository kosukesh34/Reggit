<?php

namespace App\UseCases\Admin\Project;

use App\Models\Project;
use App\Services\Admin\ProjectProvisioningService;

class ProjectStoreUseCase
{
    public function __construct(
        private readonly ProjectProvisioningService $projectProvisioningService,
    ) {}

    /**
     * @param  array<string, string|int|float|bool|array|null>  $payload  検証済みプロジェクト作成データ
     * @param  array<string, string|int|float|bool|array|null>  $settingsPayload  検証済み初期設定データ
     */
    public function handle(array $payload, array $settingsPayload): Project
    {
        $result = $this->projectProvisioningService->createWithConfig($payload, $settingsPayload, null);

        return $result['project'];
    }
}
