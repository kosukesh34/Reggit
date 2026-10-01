<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\AdminAppearanceSettings;
use App\UseCases\Admin\Settings\Data\AdminDesignSettingsView;

class GetAdminDesignSettingsUseCase
{
    public function __construct(private AdminAppearanceSettings $settings) {}

    public function handle(int $projectId): AdminDesignSettingsView
    {
        return new AdminDesignSettingsView($this->settings->getAdminSidebarColor($projectId), $this->settings->getAdminHeaderColor($projectId), $this->settings->getAdminPrimaryColor($projectId));
    }
}
