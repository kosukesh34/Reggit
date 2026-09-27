<?php

namespace App\Domain\Settings\Repositories;

interface FeatureSettings extends AdminAppearanceSettings, SettingValueReader, SettingValueWriter
{
    /** @return array<string, bool> */
    public function getFeatureFlags(int $projectId): array;

    /** @param array<string, bool> $flags */
    public function setFeatureFlags(array $flags, int $projectId): void;

    public function isFeatureEnabled(string $featureKey, int $projectId): bool;

    /** @return array<string, string> */
    public function getFeatureFlagLabels(): array;

    /** @param array<string, bool|string|int|null> $flags
     * @return array<string, bool|string|int|null>
     */
    public function withPreservedLotteryManagedFlags(array $flags, int $projectId): array;

    public function getStampDefaultImageUrl(int $projectId): ?string;

    public function getCouponDefaultImageUrl(int $projectId): ?string;

    public function getFrontendLogoUrl(int $projectId): ?string;

    public function getFrontendHeaderText(int $projectId): ?string;
}
