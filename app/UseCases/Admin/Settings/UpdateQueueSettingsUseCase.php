<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;

class UpdateQueueSettingsUseCase
{
    /** @param array<string, mixed> $validated */
    public function handle(int $projectId, array $validated): void
    {
        ShopSetting::setValue('queue_avg_serve_minutes', (int) $validated['queue_avg_serve_minutes'], 'integer', $projectId);
    }
}
