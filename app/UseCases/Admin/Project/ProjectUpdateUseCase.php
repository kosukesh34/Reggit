<?php

namespace App\UseCases\Admin\Project;

use App\Models\Project;
use App\Services\Admin\ProjectProvisioningService;
use Illuminate\Http\UploadedFile;

class ProjectUpdateUseCase
{
    public function __construct(
        private readonly ProjectProvisioningService $projectProvisioningService,
    ) {}

    /**
     * @param  array<string, string|int|float|bool|array|null>  $payload  検証済みプロジェクト更新データ
     * @param  array<string, string|int|float|bool|array|null>  $settingsPayload  検証済み初期設定データ
     */
    public function handle(
        int $id,
        array $payload,
        array $settingsPayload,
        ?UploadedFile $frontendLogo = null,
        bool $clearFrontendLogo = false,
    ): Project {
        $project = Project::findOrFail($id);
        $project->update($payload);
        $this->projectProvisioningService->syncProjectSettings((int) $project->id, $settingsPayload);
        $this->projectProvisioningService->syncSmaregiSettings((int) $project->id, $settingsPayload);
        $this->projectProvisioningService->syncFrontendLogo((int) $project->id, $frontendLogo, $clearFrontendLogo);

        return $project;
    }
}
