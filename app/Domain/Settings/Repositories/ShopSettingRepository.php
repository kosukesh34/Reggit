<?php

namespace App\Domain\Settings\Repositories;

/**
 * 店舗設定の読み書き抽象。
 */
interface ShopSettingRepository
{
    public function getValue(string $key, mixed $default, int $projectId): mixed;

    public function setValue(string $key, mixed $value, string $type, int $projectId): void;

    // ── ビジネス設定 ──

    /** @return array{start: string, end: string} */
    public function getBusinessHours(int $projectId): array;

    /** @return list<string> */
    public function getReservationTimeSlots(int $projectId): array;

    /** @return list<int> */
    public function getClosedDays(int $projectId): array;

    /** @return list<string> */
    public function getClosedDates(int $projectId): array;

    public function getAdvanceBookingDays(int $projectId): int;

    public function getReservationCapacityPerSlot(int $projectId): int;

    public function isDateAvailable(string $date, int $projectId): bool;

    // ── 順番待ち ──

    public function getQueueAvgServeMinutes(int $projectId): int;

    public function getQueueRequireMembership(int $projectId): bool;

    public function getQueueChannelLine(int $projectId): bool;

    public function getQueueChannelNative(int $projectId): bool;

    // ── オーダー ──

    public function getOrderMode(int $projectId): string;

    public function setOrderMode(string $mode, int $projectId): void;

    public function isOrderLineNotifyEnabled(int $projectId): bool;

    public function setOrderLineNotifyEnabled(bool $enabled, int $projectId): void;

    public function isOrderReadyLineNotifyEnabled(int $projectId): bool;

    public function setOrderReadyLineNotifyEnabled(bool $enabled, int $projectId): void;

    public function getKitchenAccessToken(int $projectId): string;

    public function setKitchenAccessToken(?string $token, int $projectId): void;

    // ── LINE / LIFF ──

    public function getLineLiffId(int $projectId): string;

    public function setLineLiffId(?string $liffId, int $projectId): void;

    public function getLineLiffUrl(int $projectId): string;

    public function setLineLiffUrl(?string $liffUrl, int $projectId): void;

    public function getLineChannelAccessToken(int $projectId): string;

    public function setLineChannelAccessToken(?string $token, int $projectId): void;

    public function hasLineChannelAccessToken(int $projectId): bool;

    public function getLineChannelSecret(int $projectId): string;

    public function setLineChannelSecret(?string $secret, int $projectId): void;

    public function getLineFriendAddUrl(int $projectId): string;

    public function setLineFriendAddUrl(?string $url, int $projectId): void;

    /** @return array{primary: string, primary_rgb: string, primary_dark: string, success: string, danger: string} */
    public function getLineThemeColors(int $projectId): array;

    // ── LIFF デザイナー ──

    /** @return array<string, array<int, array<string, mixed>>> */
    public function getLiffTabLayouts(int $projectId): array;

    /** @param array<string, mixed> $layouts */
    public function setLiffTabLayouts(array $layouts, int $projectId): void;

    /** @return array<string, array<int, array<string, mixed>>> */
    public function getLiffDetailLayouts(int $projectId): array;

    /** @param array<string, mixed> $layouts */
    public function setLiffDetailLayouts(array $layouts, int $projectId): void;

    public function getLiffCustomCss(int $projectId): string;

    public function setLiffCustomCss(string $css, int $projectId): void;

    /** @return array<string, mixed> */
    public function getLiffTabSettings(int $projectId): array;

    /** @param array<string, mixed> $settings */
    public function setLiffTabSettings(array $settings, int $projectId): void;

    /** @return list<array<string, mixed>> */
    public function getLiffSavedScreens(int $projectId): array;

    /** @param list<array<string, mixed>> $screens */
    public function setLiffSavedScreens(array $screens, int $projectId): void;

    public function liffSavedScreensMax(): int;

    /** @return list<array<string, mixed>> */
    public function defaultLiffDesignerTemplates(): array;

    /** @return array<string, array<int, array<string, mixed>>> */
    public function defaultLiffTabLayouts(): array;

    /** @return array<string, array<int, array<string, mixed>>> */
    public function defaultLiffDetailLayouts(): array;

    /** @return array<string, mixed> */
    public function defaultLiffTabSettings(): array;

    /** @param array<string, mixed> $layouts */
    public function ensureLiffTabLayouts(array $layouts): array;

    // ── フロントメニュー ──

    /** @return list<array<string, mixed>> */
    public function defaultFrontendMenuItems(): array;

    /** @return list<array<string, mixed>> */
    public function getFrontendMenuItems(int $projectId): array;

    /** @param list<array<string, mixed>> $items */
    public function setFrontendMenuItems(array $items, int $projectId): void;

    public function normalizeFrontendMenuIcon(string $tab, string $icon): string;

    // ── 機能フラグ ──

    /** @return array<string, bool> */
    public function getFeatureFlags(int $projectId): array;

    /** @param array<string, bool> $flags */
    public function setFeatureFlags(array $flags, int $projectId): void;

    public function isFeatureEnabled(string $featureKey, int $projectId): bool;

    /** @return array<string, string> */
    public function getFeatureFlagLabels(): array;

    /**
     * @param  array<string, bool|string|int|null>  $flags
     * @return array<string, bool|string|int|null>
     */
    public function withPreservedLotteryManagedFlags(array $flags, int $projectId): array;

    // ── ポイント・スタンプ ──

    public function getPointEarnRate(int $projectId): int;

    public function getPointEarnRateForRank(int $projectId, ?string $rankKey): int;

    public function getPointsPerOrder(int $projectId): int;

    public function getPointsValidDays(int $projectId): int;

    public function getStampEarnPerAmount(int $projectId): int;

    public function getStampEarnPerAmountForRank(int $projectId, ?string $rankKey): int;

    public function getOrderStampCardId(int $projectId): ?int;

    public function getUseStamps(int $projectId): bool;

    public function getUseTransactionStamps(int $projectId): bool;

    public function getUsePoints(int $projectId): bool;

    // ── 会員ランク ──

    /**
     * @return list<array{
     *     key: string,
     *     label: string,
     *     min_points: int,
     *     is_active: bool,
     *     card_color: string|null,
     *     order: int,
     *     point_earn_rate?: int|null,
     *     stamp_earn_per_amount?: int|null,
     *     smaregi_rank_id?: string|null
     * }>
     */
    public function getMemberRankSettings(int $projectId): array;

    /** @param list<array<string, mixed>> $settings */
    public function setMemberRankSettings(array $settings, int $projectId): void;

    public function getRankBasis(int $projectId): string;

    public function getRankAggregationType(int $projectId): string;

    public function getRankPeriodDays(int $projectId): int;

    public function getIsRankDownEnabled(int $projectId): bool;

    /** @return list<array<string, mixed>> */
    public function defaultMemberRankSettings(): array;

    // ── 会員証 ──

    public function getShowPointsDisplay(int $projectId): bool;

    public function getShowRankProgress(int $projectId): bool;

    public function getShowMemberCardForGuest(int $projectId): bool;

    public function setShowMemberCardForGuest(bool $value, int $projectId): void;

    public function getMemberCardDesign(int $projectId): string;

    public function setMemberCardDesign(string $value, int $projectId): void;

    public function getMemberCardDisplayType(int $projectId): string;

    public function setMemberCardDisplayType(string $value, int $projectId): void;

    public function getMemberCardCodeType(int $projectId): string;

    public function setMemberCardCodeType(string $value, int $projectId): void;

    // ── 会員コード ──

    public function getMemberCodeType(int $projectId): string;

    public function getMemberCodeFormat(int $projectId): string;

    public function getMemberCodeLength(int $projectId): int;

    public function getMemberCodePrefix(int $projectId): string;

    public function getMemberCodeNextNumber(int $projectId): int;

    public function setMemberCodeNextNumber(int $nextNumber, int $projectId): void;

    // ── プロフィール ──

    /**
     * @return array{
     *     smaregi_fields: array<string, array{label: string, enabled: bool, required: bool}>,
     *     custom_fields: list<array<string, scalar|null>>
     * }
     */
    public function getProfileFieldSettings(int $projectId): array;

    /** @param array{smaregi_fields: array<string, array{enabled?: bool, required?: bool}>, custom_fields?: list<array<string, mixed>>} $settings */
    public function setProfileFieldSettings(array $settings, int $projectId): void;

    /** @return array{smaregi_fields: array<string, array{label: string, enabled: bool, required: bool}>, custom_fields: list<mixed>} */
    public function defaultProfileFieldSettings(): array;

    public function getMemberRegistrationMode(int $projectId): string;

    public function setMemberRegistrationMode(string $mode, int $projectId): void;

    // ── UI カスタマイズ ──

    public function getAdminSidebarColor(int $projectId): ?string;

    public function getAdminHeaderColor(int $projectId): ?string;

    public function getAdminPrimaryColor(int $projectId): ?string;

    public function getStampDefaultImageUrl(int $projectId): ?string;

    public function getCouponDefaultImageUrl(int $projectId): ?string;

    public function getFrontendLogoUrl(int $projectId): ?string;

    public function getFrontendHeaderText(int $projectId): ?string;

    // ── Wallet ──

    public function getWalletGoogleProgramLogoUrl(int $projectId): ?string;

    public function setWalletGoogleProgramLogoUrl(?string $url, int $projectId): void;

    public function getWalletGoogleHeroImageUrl(int $projectId): ?string;

    public function setWalletGoogleHeroImageUrl(?string $url, int $projectId): void;
}
