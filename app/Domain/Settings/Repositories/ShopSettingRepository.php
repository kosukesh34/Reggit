<?php

namespace App\Domain\Settings\Repositories;

/**
 * 店舗設定の読み取り抽象。ShopSetting 静的 API からの段階移行用。
 */
interface ShopSettingRepository
{
    public function getOrderMode(int $projectId): string;

    public function getPointsValidDays(int $projectId): int;

    public function getPointsPerOrder(int $projectId): int;

    public function getPointEarnRate(int $projectId): int;

    public function getStampEarnPerAmount(int $projectId): int;

    public function getOrderStampCardId(int $projectId): ?int;

    public function getCouponDefaultImageUrl(int $projectId): ?string;

    public function getQueueAvgServeMinutes(int $projectId): int;

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
     *     order: int
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

    public function getShowMemberCardForGuest(int $projectId): bool;

    public function getMemberCodeType(int $projectId): string;

    public function getMemberCodeFormat(int $projectId): string;

    public function getMemberCodeLength(int $projectId): int;

    public function getMemberCodePrefix(int $projectId): string;

    public function getMemberCodeNextNumber(int $projectId): int;

    public function setMemberCodeNextNumber(int $nextNumber, int $projectId): void;

    public function isFeatureEnabled(string $featureKey, int $projectId): bool;
}
