<?php

namespace App\Domain\Settings\Repositories;

interface ProjectSettings extends SettingValueReader, SettingValueWriter
{
    public function getFrontendLogoUrl(int $projectId): ?string;

    public function getFrontendHeaderText(int $projectId): ?string;

    public function getLineLiffId(int $projectId): string;

    public function setLineLiffId(?string $liffId, int $projectId): void;

    public function getLineLiffUrl(int $projectId): string;

    public function setLineLiffUrl(?string $liffUrl, int $projectId): void;
}
