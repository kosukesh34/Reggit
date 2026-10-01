<?php

namespace App\Domain\Settings\Repositories;

interface MemberCodeSettings extends SettingValueWriter
{
    public function getMemberCodeType(int $projectId): string;

    public function getMemberCodeFormat(int $projectId): string;

    public function getMemberCodeLength(int $projectId): int;

    public function getMemberCodePrefix(int $projectId): string;
}
