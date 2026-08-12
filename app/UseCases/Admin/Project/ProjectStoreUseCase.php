<?php

namespace App\UseCases\Admin\Project;

use App\Models\Project;
use App\Services\Admin\ProjectProvisioningService;
use Illuminate\Http\UploadedFile;

class ProjectStoreUseCase
{
    public function __construct(
        private readonly ProjectProvisioningService $projectProvisioningService,
    ) {}

    /**
     * @param  array<string, string|int|float|bool|array|null>  $payload  検証済みプロジェクト作成データ
     * @param  array<string, string|int|float|bool|array|null>  $settingsPayload  検証済み初期設定データ
     */
    public function handle(array $payload, array $settingsPayload, ?UploadedFile $frontendLogo = null): Project
    {
        $result = $this->projectProvisioningService->createWithConfig($payload, $settingsPayload, null);
        $this->projectProvisioningService->syncFrontendLogo(
            (int) $result['project']->id,
            $frontendLogo,
            false,
        );

        return $result['project'];
    }
}
