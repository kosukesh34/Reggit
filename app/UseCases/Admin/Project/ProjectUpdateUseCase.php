<?php

namespace App\UseCases\Admin\Project;

use App\Models\Project;
use App\Services\Admin\ProjectProvisioningService;
use App\UseCases\Admin\Settings\UpdateLiffConnectionSettingsUseCase;

class ProjectUpdateUseCase
{
    public function __construct(
        private readonly ProjectProvisioningService $projectProvisioningService,
        private readonly UpdateLiffConnectionSettingsUseCase $updateLiffConnectionSettingsUseCase,
    ) {}

    /**
     * @param  array<string, string|int|float|bool|array|null>  $payload  検証済みプロジェクト更新データ
     * @param  array<string, string|int|float|bool|array|null>  $settingsPayload  検証済み初期設定データ
     * @param  array{line_liff_id: string, line_liff_url: string, line_friend_add_url?: string|null, line_channel_access_token?: string|null, line_channel_secret?: string|null}|null  $lineConnectionPayload  送られていなければ LINE 設定は変えない
     */
    public function handle(
        int $id,
        array $payload,
        array $settingsPayload,
        ?array $lineConnectionPayload = null,
    ): Project {
        $project = Project::findOrFail($id);
        $project->update($payload);
        $this->projectProvisioningService->syncSmaregiSettings((int) $project->id, $settingsPayload);
        if ($lineConnectionPayload !== null) {
            $this->updateLiffConnectionSettingsUseCase->handle((int) $project->id, $lineConnectionPayload);
        }

        return $project;
    }
}
