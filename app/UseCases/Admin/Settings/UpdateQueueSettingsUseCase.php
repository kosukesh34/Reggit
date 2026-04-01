<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;

class UpdateQueueSettingsUseCase
{
    /** @param array<string, bool|int|float|string|array|object|null> $validated */
    public function handle(int $projectId, array $validated): void
    {
        ShopSetting::setValue('queue_avg_serve_minutes', (int) $validated['queue_avg_serve_minutes'], 'integer', $projectId);
    }
}
