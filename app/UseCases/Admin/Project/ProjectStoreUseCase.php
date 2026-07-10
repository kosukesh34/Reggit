<?php

namespace App\UseCases\Admin\Project;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Models\Project;
use App\Services\Line\LineLiffAdminService;

class ProjectStoreUseCase
{
    public function __construct(
        private readonly LineLiffAdminService $lineLiffAdminService,
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    /**
     * @param  array<string, string|int|float|bool|array|null>  $payload  検証済みプロジェクト作成データ
     * @param  array<string, string|int|float|bool|array|null>  $settingsPayload  検証済み初期設定データ
     */
    public function handle(array $payload, array $settingsPayload): Project
    {
        $project = Project::create($payload);
        $this->syncProjectSettings((int) $project->id, $settingsPayload);

        return $project;
    }

    /** @param array<string, string|int|float|bool|array|null> $settingsPayload */
    private function syncProjectSettings(int $projectId, array $settingsPayload): void
    {
        $liffId = (string) ($settingsPayload['line_liff_id'] ?? '');
        $this->shopSettingRepository->setLineLiffId($liffId, $projectId);
        $this->shopSettingRepository->setLineLiffUrl((string) ($settingsPayload['line_liff_url'] ?? ''), $projectId);
        $this->shopSettingRepository->setValue('frontend_header_text', (string) ($settingsPayload['frontend_header_text'] ?? ''), 'string', $projectId);
        $this->shopSettingRepository->setValue('frontend_logo_url', (string) ($settingsPayload['frontend_logo_url'] ?? ''), 'string', $projectId);
        $this->shopSettingRepository->setValue('business_hours_start', (string) ($settingsPayload['business_hours_start'] ?? '10:00'), 'time', $projectId);
        $this->shopSettingRepository->setValue('business_hours_end', (string) ($settingsPayload['business_hours_end'] ?? '22:00'), 'time', $projectId);
        $this->shopSettingRepository->setValue('line_primary_color', (string) ($settingsPayload['line_primary_color'] ?? '#000000'), 'string', $projectId);
        $this->shopSettingRepository->setValue('line_primary_dark', (string) ($settingsPayload['line_primary_dark'] ?? '#333333'), 'string', $projectId);
        $this->shopSettingRepository->setValue('line_success_color', (string) ($settingsPayload['line_success_color'] ?? '#000000'), 'string', $projectId);
        $this->shopSettingRepository->setValue('line_danger_color', (string) ($settingsPayload['line_danger_color'] ?? '#dc3545'), 'string', $projectId);

        if (config('line.auto_enable_liff_module_mode', true) && trim($liffId) !== '') {
            $this->lineLiffAdminService->hideActionMenu($liffId);
        }
    }
}
