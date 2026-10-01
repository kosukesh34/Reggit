<?php

namespace App\Domain\Settings\Repositories;

interface MemberCardSettings extends SettingValueReader, SettingValueWriter
{
    public function getShowPointsDisplay(int $projectId): bool;

    public function getShowRankProgress(int $projectId): bool;

    public function getShowMemberCardForGuest(int $projectId): bool;

    public function getMemberCardDesign(int $projectId): string;

    public function getMemberCardDisplayType(int $projectId): string;

    public function getMemberCardCodeType(int $projectId): string;

    public function setShowMemberCardForGuest(bool $value, int $projectId): void;

    public function setMemberCardDesign(string $value, int $projectId): void;

    public function setMemberCardDisplayType(string $value, int $projectId): void;

    public function setMemberCardCodeType(string $value, int $projectId): void;
}
