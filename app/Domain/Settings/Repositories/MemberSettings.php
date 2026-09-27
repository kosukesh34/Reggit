<?php

namespace App\Domain\Settings\Repositories;

interface MemberSettings extends ProfileSettings, RankSettings
{
    public function getMemberCodeNextNumber(int $projectId): int;

    public function setMemberCodeNextNumber(int $nextNumber, int $projectId): void;
}
