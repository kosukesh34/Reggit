<?php

namespace App\Domain\Settings\Repositories;

interface LiffDesignerSettings extends SettingValueReader, SettingValueWriter
{
    public function getLiffTabSettings(int $projectId): array;

    public function setLiffTabSettings(array $settings, int $projectId): void;
}
