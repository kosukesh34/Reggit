<?php

namespace App\UseCases\Admin\Settings\Data;

final readonly class AdminDesignSettingsView
{
    public function __construct(public ?string $adminSidebarColor, public ?string $adminPrimaryColor) {}
}
