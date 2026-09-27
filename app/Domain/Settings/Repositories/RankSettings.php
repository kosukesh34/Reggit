<?php

namespace App\Domain\Settings\Repositories;

interface RankSettings extends MemberCardPreviewSettings, SettingValueReader, SettingValueWriter
{
    /** @param list<array<string, mixed>> $settings */
    public function setMemberRankSettings(array $settings, int $projectId): void;

    public function getIsRankDownEnabled(int $projectId): bool;

    /** @return list<array<string, mixed>> */
    public function defaultMemberRankSettings(): array;

    public function getPointEarnRate(int $projectId): int;

    public function getPointEarnRateForRank(int $projectId, ?string $rankKey): int;

    public function getPointsPerOrder(int $projectId): int;

    public function getPointsValidDays(int $projectId): int;

    public function getStampEarnPerAmount(int $projectId): int;

    public function getUseStamps(int $projectId): bool;

    public function getUseTransactionStamps(int $projectId): bool;

    public function setShowMemberCardForGuest(bool $value, int $projectId): void;

    public function setMemberCardDesign(string $value, int $projectId): void;

    public function setMemberCardDisplayType(string $value, int $projectId): void;

    public function setMemberCardCodeType(string $value, int $projectId): void;

    public function getMemberCodeType(int $projectId): string;

    public function getMemberCodeFormat(int $projectId): string;

    public function getMemberCodeLength(int $projectId): int;

    public function getMemberCodePrefix(int $projectId): string;

    /** @return array<string, bool> */
    public function getFeatureFlags(int $projectId): array;

    /** @param array<string, bool> $flags */
    public function setFeatureFlags(array $flags, int $projectId): void;
}
