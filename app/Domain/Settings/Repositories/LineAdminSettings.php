<?php

namespace App\Domain\Settings\Repositories;

interface LineAdminSettings extends LineConnectionSettings, SettingValueReader
{
    public function setLineLiffId(?string $liffId, int $projectId): void;

    public function setLineLiffUrl(?string $liffUrl, int $projectId): void;

    public function setLineChannelAccessToken(?string $token, int $projectId): void;

    public function setLineChannelSecret(?string $secret, int $projectId): void;

    public function getLineFriendAddUrl(int $projectId): string;

    public function setLineFriendAddUrl(?string $url, int $projectId): void;
}
