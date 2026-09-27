<?php

namespace App\Domain\Settings\Repositories;

interface LiffDesignerSettings extends SettingValueWriter
{
    public function setMemberCardDesign(string $value, int $projectId): void;

    public function setMemberCardDisplayType(string $value, int $projectId): void;

    public function setMemberCardCodeType(string $value, int $projectId): void;

    public function setMemberRegistrationMode(string $mode, int $projectId): void;
}
