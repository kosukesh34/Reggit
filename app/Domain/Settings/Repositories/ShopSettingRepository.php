<?php

namespace App\Domain\Settings\Repositories;

/**
 * 店舗設定の読み書き抽象。ShopSetting 静的 API からの段階移行用。
 */
interface ShopSettingRepository
{
    public function getOrderMode(int $projectId): string;

    public function getPointsValidDays(int $projectId): int;

    public function getPointsPerOrder(int $projectId): int;

    public function getPointEarnRate(int $projectId): int;

    public function getPointEarnRateForRank(int $projectId, ?string $rankKey): int;

    public function getStampEarnPerAmount(int $projectId): int;

    public function getOrderStampCardId(int $projectId): ?int;

    public function getCouponDefaultImageUrl(int $projectId): ?string;

    public function getQueueAvgServeMinutes(int $projectId): int;

    public function getQueueRequireMembership(int $projectId): bool;

    public function getQueueChannelLine(int $projectId): bool;

    public function getQueueChannelNative(int $projectId): bool;

    /** @return list<string> */
    public function getReservationTimeSlots(int $projectId): array;

    public function getAdvanceBookingDays(int $projectId): int;

    public function getReservationCapacityPerSlot(int $projectId): int;

    public function isDateAvailable(string $date, int $projectId): bool;

    /**
     * @return list<array{
     *     key: string,
     *     label: string,
     *     min_points: int,
     *     is_active: bool,
     *     icon_url: string|null,
     *     stamp_background_url?: string|null,
     *     card_color: string|null,
     *     order: int,
     *     point_earn_rate?: int|null,
     *     smaregi_rank_id?: string|null
     * }>
     */
    public function getMemberRankSettings(int $projectId): array;

    public function getRankBasis(int $projectId): string;

    public function getStampDefaultImageUrl(int $projectId): ?string;

    public function getShowPointsDisplay(int $projectId): bool;

    public function getShowRankProgress(int $projectId): bool;

    public function getMemberCardDesign(int $projectId): string;

    public function getFrontendLogoUrl(int $projectId): ?string;

    public function getMemberCardDisplayType(int $projectId): string;

    public function getMemberCardCodeType(int $projectId): string;

    /**
     * @return array{
     *     smaregi_fields: array<string, array{label: string, enabled: bool, required: bool}>,
     *     custom_fields: list<array<string, scalar|null>>
     * }
     */
    public function getProfileFieldSettings(int $projectId): array;

    public function getMemberRegistrationMode(int $projectId): string;

    public function getShowMemberCardForGuest(int $projectId): bool;

    public function getMemberCodeType(int $projectId): string;

    public function getMemberCodeFormat(int $projectId): string;

    public function getMemberCodeLength(int $projectId): int;

    public function getMemberCodePrefix(int $projectId): string;

    public function getMemberCodeNextNumber(int $projectId): int;

    public function setMemberCodeNextNumber(int $nextNumber, int $projectId): void;

    public function isFeatureEnabled(string $featureKey, int $projectId): bool;

    public function getValue(string $key, mixed $default, int $projectId): mixed;

    /** @return array<string, bool> */
    public function getFeatureFlags(int $projectId): array;

    /** @return array<string, string> */
    public function getFeatureFlagLabels(): array;

    public function getAdminSidebarColor(int $projectId): string;

    public function getAdminHeaderColor(int $projectId): string;

    public function getAdminPrimaryColor(int $projectId): string;

    public function getFrontendHeaderText(int $projectId): string;

    public function getWalletGoogleProgramLogoUrl(int $projectId): ?string;

    public function getWalletGoogleHeroImageUrl(int $projectId): ?string;

    /** @return list<array<string, mixed>> */
    public function defaultFrontendMenuItems(): array;

    public function setValue(string $key, mixed $value, string $type, int $projectId): void;

    /** @param array<string, bool> $flags */
    public function setFeatureFlags(array $flags, int $projectId): void;

    /** @param list<array<string, mixed>> $items */
    public function setFrontendMenuItems(array $items, int $projectId): void;

    /** @param list<array<string, mixed>> $settings */
    public function setMemberRankSettings(array $settings, int $projectId): void;

    /** @param array{smaregi_fields: array<string, array{enabled?: bool, required?: bool}>, custom_fields?: list<array<string, mixed>>} $settings */
    public function setProfileFieldSettings(array $settings, int $projectId): void;

    public function setMemberRegistrationMode(string $mode, int $projectId): void;

    public function setShowMemberCardForGuest(bool $value, int $projectId): void;

    public function setMemberCardDesign(string $value, int $projectId): void;

    public function setMemberCardDisplayType(string $value, int $projectId): void;

    public function setMemberCardCodeType(string $value, int $projectId): void;

    public function setWalletGoogleProgramLogoUrl(?string $url, int $projectId): void;

    public function setWalletGoogleHeroImageUrl(?string $url, int $projectId): void;

    public function setLineLiffId(?string $liffId, int $projectId): void;

    public function setLineLiffUrl(?string $liffUrl, int $projectId): void;
}
