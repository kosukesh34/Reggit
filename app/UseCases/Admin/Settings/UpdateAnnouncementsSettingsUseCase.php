<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\SettingValueWriter;
use App\UseCases\Admin\Settings\Data\UpdateAnnouncementsInput;

class UpdateAnnouncementsSettingsUseCase
{
    public function __construct(private SettingValueWriter $settings) {}

    public function handle(UpdateAnnouncementsInput $input): void
    {
        $this->settings->setValue('announcement_default_pinned', $input->defaultPinned ? '1' : '0', 'boolean', $input->projectId);
        $this->settings->setValue('announcement_default_expiry_days', $input->defaultExpiryDays, 'integer', $input->projectId);
    }
}
