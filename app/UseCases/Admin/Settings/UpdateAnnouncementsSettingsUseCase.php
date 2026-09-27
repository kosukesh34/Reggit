<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\FeatureSettings;

class UpdateAnnouncementsSettingsUseCase
{
    public function __construct(
        private FeatureSettings $shopSettingRepository,
    ) {}

    /** @param array<string, mixed> $validated */
    public function handle(int $projectId, array $validated): void
    {
        $flags = $this->shopSettingRepository->getFeatureFlags($projectId);
        $flags['feature_announcements_enabled'] = (bool) ($validated['feature_announcements_enabled'] ?? false);
        $this->shopSettingRepository->setFeatureFlags($flags, $projectId);
        $this->shopSettingRepository->setValue('announcement_default_pinned', isset($validated['announcement_default_pinned']) ? '1' : '0', 'boolean', $projectId);
        $this->shopSettingRepository->setValue('announcement_default_expiry_days', (int) ($validated['announcement_default_expiry_days'] ?? 0), 'integer', $projectId);
    }
}
