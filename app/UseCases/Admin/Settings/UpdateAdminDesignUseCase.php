<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\SettingValueWriter;
use App\UseCases\Admin\Settings\Data\UpdateAdminDesignInput;

class UpdateAdminDesignUseCase
{
    public function __construct(private SettingValueWriter $settings) {}

    public function handle(UpdateAdminDesignInput $input): void
    {
        foreach (['admin_sidebar_color' => $input->sidebarColor, 'admin_header_color' => $input->headerColor, 'admin_primary_color' => $input->primaryColor] as $key => $color) {
            $this->settings->setValue($key, $color, 'string', $input->projectId);
        }
    }
}
