<?php

namespace App\UseCases\Admin\Rfm;

use App\Models\Settings\ProjectRfmSetting;

class AdminRfmSettingsUseCase
{
    /** 判定基準（未保存なら既定値） */
    public function current(int $projectId): ProjectRfmSetting
    {
        return ProjectRfmSetting::forProject($projectId);
    }

    /** @param array{recency_active_days: int, recency_sleeping_days: int, frequency_high: int, monetary_high: int} $thresholds */
    public function save(int $projectId, array $thresholds): void
    {
        ProjectRfmSetting::updateOrCreate(['project_id' => $projectId], $thresholds);
    }
}
