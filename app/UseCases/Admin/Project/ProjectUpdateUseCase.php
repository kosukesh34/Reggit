<?php

namespace App\UseCases\Admin\Project;

use App\Models\Project;
use App\Models\ShopSetting;

class ProjectUpdateUseCase
{
    /**
     * @param array<string, string|int|float|bool|array|null> $payload 検証済みプロジェクト更新データ
     * @param array<string, string|int|float|bool|array|null> $settingsPayload 検証済み初期設定データ
     */
    public function handle(int $id, array $payload, array $settingsPayload): Project
    {
        $project = Project::findOrFail($id);
        $project->update($payload);
        $this->syncProjectSettings((int) $project->id, $settingsPayload);
        return $project;
    }

    /** @param array<string, string|int|float|bool|array|null> $settingsPayload */
    private function syncProjectSettings(int $projectId, array $settingsPayload): void
    {
        ShopSetting::setLineLiffId((string) ($settingsPayload['line_liff_id'] ?? ''), $projectId);
        ShopSetting::setLineLiffUrl((string) ($settingsPayload['line_liff_url'] ?? ''), $projectId);
        ShopSetting::setValue('frontend_header_text', (string) ($settingsPayload['frontend_header_text'] ?? ''), 'string', $projectId);
        ShopSetting::setValue('frontend_logo_url', (string) ($settingsPayload['frontend_logo_url'] ?? ''), 'string', $projectId);
        ShopSetting::setValue('business_hours_start', (string) ($settingsPayload['business_hours_start'] ?? '10:00'), 'time', $projectId);
        ShopSetting::setValue('business_hours_end', (string) ($settingsPayload['business_hours_end'] ?? '22:00'), 'time', $projectId);
        ShopSetting::setValue('line_primary_color', (string) ($settingsPayload['line_primary_color'] ?? '#000000'), 'string', $projectId);
        ShopSetting::setValue('line_primary_dark', (string) ($settingsPayload['line_primary_dark'] ?? '#333333'), 'string', $projectId);
        ShopSetting::setValue('line_success_color', (string) ($settingsPayload['line_success_color'] ?? '#000000'), 'string', $projectId);
        ShopSetting::setValue('line_danger_color', (string) ($settingsPayload['line_danger_color'] ?? '#dc3545'), 'string', $projectId);
    }
}
