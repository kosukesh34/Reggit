<?php

namespace App\Domain\Settings\Repositories;

interface AdminAppearanceSettings
{
    public function getAdminSidebarColor(int $projectId): ?string;

    public function getAdminHeaderColor(int $projectId): ?string;

    public function getAdminPrimaryColor(int $projectId): ?string;
}
