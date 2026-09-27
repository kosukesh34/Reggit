<?php

namespace App\Infrastructure\Persistence\Eloquent\Settings;

use App\Domain\Settings\Repositories\AdminAppearanceSettings;
use App\Domain\Settings\Repositories\AppConfigSettings;
use App\Domain\Settings\Repositories\CouponNotificationSettings;
use App\Domain\Settings\Repositories\FrontendShellSettings;
use App\Domain\Settings\Repositories\HomeSettingsRepository;
use App\Domain\Settings\Repositories\LineConnectionSettings;
use App\Domain\Settings\Repositories\MemberCardPreviewSettings;
use App\Domain\Settings\Repositories\OrderNotificationSettings;
use App\Domain\Settings\Repositories\QueueSettings;
use App\Domain\Settings\Repositories\ReservationSettings;
use App\Domain\Settings\Repositories\ShopSettingRepository;

class EloquentShopSettingRepository implements AdminAppearanceSettings, AppConfigSettings, CouponNotificationSettings, FrontendShellSettings, HomeSettingsRepository, LineConnectionSettings, MemberCardPreviewSettings, OrderNotificationSettings, QueueSettings, ReservationSettings, ShopSettingRepository
{
    public function getValue(string $key, mixed $default, int $projectId): mixed
    {
        return ShopSettingStore::getValue($key, $default, $projectId);
    }

    public function setValue(string $key, mixed $value, string $type, int $projectId): void
    {
        ShopSettingStore::setValue($key, $value, $type, $projectId);
    }

    // ── ビジネス設定 ──

    public function getBusinessHours(int $projectId): array
    {
        return ShopSettingStore::getBusinessHours($projectId);
    }

    public function getReservationTimeSlots(int $projectId): array
    {
        return ShopSettingStore::getReservationTimeSlots($projectId);
    }

    public function getClosedDays(int $projectId): array
    {
        return ShopSettingStore::getClosedDays($projectId);
    }

    public function getClosedDates(int $projectId): array
    {
        return ShopSettingStore::getClosedDates($projectId);
    }

    public function getAdvanceBookingDays(int $projectId): int
    {
        return ShopSettingStore::getAdvanceBookingDays($projectId);
    }

    public function getReservationCapacityPerSlot(int $projectId): int
    {
        return ShopSettingStore::getReservationCapacityPerSlot($projectId);
    }

    public function isDateAvailable(string $date, int $projectId): bool
    {
        return ShopSettingStore::isDateAvailable($date, $projectId);
    }

    // ── 順番待ち ──

    public function getQueueAvgServeMinutes(int $projectId): int
    {
        return (int) ShopSettingStore::getQueueAvgServeMinutes($projectId);
    }

    public function getQueueRequireMembership(int $projectId): bool
    {
        return ShopSettingStore::getQueueRequireMembership($projectId);
    }

    public function getQueueChannelLine(int $projectId): bool
    {
        return ShopSettingStore::getQueueChannelLine($projectId);
    }

    public function getQueueChannelNative(int $projectId): bool
    {
        return ShopSettingStore::getQueueChannelNative($projectId);
    }

    // ── オーダー ──

    public function getOrderMode(int $projectId): string
    {
        return ShopSettingStore::getOrderMode($projectId);
    }

    public function setOrderMode(string $mode, int $projectId): void
    {
        ShopSettingStore::setOrderMode($mode, $projectId);
    }

    public function isOrderLineNotifyEnabled(int $projectId): bool
    {
        return ShopSettingStore::isOrderLineNotifyEnabled($projectId);
    }

    public function setOrderLineNotifyEnabled(bool $enabled, int $projectId): void
    {
        ShopSettingStore::setOrderLineNotifyEnabled($enabled, $projectId);
    }

    public function isOrderReadyLineNotifyEnabled(int $projectId): bool
    {
        return ShopSettingStore::isOrderReadyLineNotifyEnabled($projectId);
    }

    public function setOrderReadyLineNotifyEnabled(bool $enabled, int $projectId): void
    {
        ShopSettingStore::setOrderReadyLineNotifyEnabled($enabled, $projectId);
    }

    public function getKitchenAccessToken(int $projectId): string
    {
        return ShopSettingStore::getKitchenAccessToken($projectId);
    }

    public function setKitchenAccessToken(?string $token, int $projectId): void
    {
        ShopSettingStore::setKitchenAccessToken($token, $projectId);
    }

    // ── LINE / LIFF ──

    public function getLineLiffId(int $projectId): string
    {
        return ShopSettingStore::getLineLiffId($projectId);
    }

    public function setLineLiffId(?string $liffId, int $projectId): void
    {
        ShopSettingStore::setLineLiffId($liffId, $projectId);
    }

    public function getLineLiffUrl(int $projectId): string
    {
        return ShopSettingStore::getLineLiffUrl($projectId);
    }

    public function setLineLiffUrl(?string $liffUrl, int $projectId): void
    {
        ShopSettingStore::setLineLiffUrl($liffUrl, $projectId);
    }

    public function getLineChannelAccessToken(int $projectId): string
    {
        return ShopSettingStore::getLineChannelAccessToken($projectId);
    }

    public function setLineChannelAccessToken(?string $token, int $projectId): void
    {
        ShopSettingStore::setLineChannelAccessToken($token, $projectId);
    }

    public function hasLineChannelAccessToken(int $projectId): bool
    {
        return ShopSettingStore::hasLineChannelAccessToken($projectId);
    }

    public function getLineChannelSecret(int $projectId): string
    {
        return ShopSettingStore::getLineChannelSecret($projectId);
    }

    public function setLineChannelSecret(?string $secret, int $projectId): void
    {
        ShopSettingStore::setLineChannelSecret($secret, $projectId);
    }

    public function getLineFriendAddUrl(int $projectId): string
    {
        return ShopSettingStore::getLineFriendAddUrl($projectId);
    }

    public function setLineFriendAddUrl(?string $url, int $projectId): void
    {
        ShopSettingStore::setLineFriendAddUrl($url, $projectId);
    }

    public function getLineThemeColors(int $projectId): array
    {
        return ShopSettingStore::getLineThemeColors($projectId);
    }

    // ── LIFF デザイナー ──

    public function getLiffTabLayouts(int $projectId): array
    {
        return ShopSettingStore::getLiffTabLayouts($projectId);
    }

    public function setLiffTabLayouts(array $layouts, int $projectId): void
    {
        ShopSettingStore::setLiffTabLayouts($layouts, $projectId);
    }

    public function getLiffDetailLayouts(int $projectId): array
    {
        return ShopSettingStore::getLiffDetailLayouts($projectId);
    }

    public function setLiffDetailLayouts(array $layouts, int $projectId): void
    {
        ShopSettingStore::setLiffDetailLayouts($layouts, $projectId);
    }

    public function getLiffCustomCss(int $projectId): string
    {
        return ShopSettingStore::getLiffCustomCss($projectId);
    }

    public function setLiffCustomCss(string $css, int $projectId): void
    {
        ShopSettingStore::setLiffCustomCss($css, $projectId);
    }

    public function getLiffTabSettings(int $projectId): array
    {
        return ShopSettingStore::getLiffTabSettings($projectId);
    }

    public function setLiffTabSettings(array $settings, int $projectId): void
    {
        ShopSettingStore::setLiffTabSettings($settings, $projectId);
    }

    public function getLiffSavedScreens(int $projectId): array
    {
        return ShopSettingStore::getLiffSavedScreens($projectId);
    }

    public function setLiffSavedScreens(array $screens, int $projectId): void
    {
        ShopSettingStore::setLiffSavedScreens($screens, $projectId);
    }

    public function liffSavedScreensMax(): int
    {
        return ShopSettingStore::LIFF_SAVED_SCREENS_MAX;
    }

    public function defaultLiffDesignerTemplates(): array
    {
        return ShopSettingStore::defaultLiffDesignerTemplates();
    }

    public function defaultLiffTabLayouts(): array
    {
        return ShopSettingStore::defaultLiffTabLayouts();
    }

    public function defaultLiffDetailLayouts(): array
    {
        return ShopSettingStore::defaultLiffDetailLayouts();
    }

    public function defaultLiffTabSettings(): array
    {
        return ShopSettingStore::defaultLiffTabSettings();
    }

    public function ensureLiffTabLayouts(array $layouts): array
    {
        return ShopSettingStore::ensureLiffTabLayouts($layouts);
    }

    // ── フロントメニュー ──

    public function defaultFrontendMenuItems(): array
    {
        return ShopSettingStore::defaultFrontendMenuItems();
    }

    public function getFrontendMenuItems(int $projectId): array
    {
        return ShopSettingStore::getFrontendMenuItems($projectId);
    }

    public function setFrontendMenuItems(array $items, int $projectId): void
    {
        ShopSettingStore::setFrontendMenuItems($items, $projectId);
    }

    public function normalizeFrontendMenuIcon(string $tab, string $icon): string
    {
        return ShopSettingStore::normalizeFrontendMenuIcon($tab, $icon);
    }

    // ── 機能フラグ ──

    public function getFeatureFlags(int $projectId): array
    {
        return ShopSettingStore::getFeatureFlags($projectId);
    }

    public function setFeatureFlags(array $flags, int $projectId): void
    {
        ShopSettingStore::setFeatureFlags($flags, $projectId);
    }

    public function isFeatureEnabled(string $featureKey, int $projectId): bool
    {
        return ShopSettingStore::isFeatureEnabled($featureKey, $projectId);
    }

    public function getFeatureFlagLabels(): array
    {
        return ShopSettingStore::featureFlagLabels();
    }

    public function withPreservedLotteryManagedFlags(array $flags, int $projectId): array
    {
        return ShopSettingStore::withPreservedLotteryManagedFlags($flags, $projectId);
    }

    // ── ポイント・スタンプ ──

    public function getPointEarnRate(int $projectId): int
    {
        return (int) ShopSettingStore::getPointEarnRate($projectId);
    }

    public function getPointEarnRateForRank(int $projectId, ?string $rankKey): int
    {
        return (int) ShopSettingStore::getPointEarnRateForRank($projectId, $rankKey);
    }

    public function getPointsPerOrder(int $projectId): int
    {
        return (int) ShopSettingStore::getPointsPerOrder($projectId);
    }

    public function getPointsValidDays(int $projectId): int
    {
        return ShopSettingStore::getPointsValidDays($projectId);
    }

    public function getStampEarnPerAmount(int $projectId): int
    {
        return (int) ShopSettingStore::getStampEarnPerAmount($projectId);
    }

    public function getStampEarnPerAmountForRank(int $projectId, ?string $rankKey): int
    {
        return (int) ShopSettingStore::getStampEarnPerAmountForRank($projectId, $rankKey);
    }

    public function getOrderStampCardId(int $projectId): ?int
    {
        return ShopSettingStore::getOrderStampCardId($projectId);
    }

    public function getUseStamps(int $projectId): bool
    {
        return ShopSettingStore::getUseStamps($projectId);
    }

    public function getUseTransactionStamps(int $projectId): bool
    {
        return ShopSettingStore::getUseTransactionStamps($projectId);
    }

    public function getUsePoints(int $projectId): bool
    {
        return ShopSettingStore::getUsePoints($projectId);
    }

    // ── 会員ランク ──

    public function getMemberRankSettings(int $projectId): array
    {
        return ShopSettingStore::getMemberRankSettings($projectId);
    }

    public function setMemberRankSettings(array $settings, int $projectId): void
    {
        ShopSettingStore::setMemberRankSettings($settings, $projectId);
    }

    public function getRankBasis(int $projectId): string
    {
        return ShopSettingStore::getRankBasis($projectId);
    }

    public function getRankAggregationType(int $projectId): string
    {
        return ShopSettingStore::getRankAggregationType($projectId);
    }

    public function getRankPeriodDays(int $projectId): int
    {
        return ShopSettingStore::getRankPeriodDays($projectId);
    }

    public function getIsRankDownEnabled(int $projectId): bool
    {
        return ShopSettingStore::getIsRankDownEnabled($projectId);
    }

    public function defaultMemberRankSettings(): array
    {
        return ShopSettingStore::defaultMemberRankSettings();
    }

    // ── 会員証 ──

    public function getShowPointsDisplay(int $projectId): bool
    {
        return (bool) ShopSettingStore::getShowPointsDisplay($projectId);
    }

    public function getShowRankProgress(int $projectId): bool
    {
        return (bool) ShopSettingStore::getShowRankProgress($projectId);
    }

    public function getShowMemberCardForGuest(int $projectId): bool
    {
        return (bool) ShopSettingStore::getShowMemberCardForGuest($projectId);
    }

    public function setShowMemberCardForGuest(bool $value, int $projectId): void
    {
        ShopSettingStore::setShowMemberCardForGuest($value, $projectId);
    }

    public function getMemberCardDesign(int $projectId): string
    {
        return (string) ShopSettingStore::getMemberCardDesign($projectId);
    }

    public function setMemberCardDesign(string $value, int $projectId): void
    {
        ShopSettingStore::setMemberCardDesign($value, $projectId);
    }

    public function getMemberCardDisplayType(int $projectId): string
    {
        return (string) ShopSettingStore::getMemberCardDisplayType($projectId);
    }

    public function setMemberCardDisplayType(string $value, int $projectId): void
    {
        ShopSettingStore::setMemberCardDisplayType($value, $projectId);
    }

    public function getMemberCardCodeType(int $projectId): string
    {
        return (string) ShopSettingStore::getMemberCardCodeType($projectId);
    }

    public function setMemberCardCodeType(string $value, int $projectId): void
    {
        ShopSettingStore::setMemberCardCodeType($value, $projectId);
    }

    // ── 会員コード ──

    public function getMemberCodeType(int $projectId): string
    {
        return (string) ShopSettingStore::getMemberCodeType($projectId);
    }

    public function getMemberCodeFormat(int $projectId): string
    {
        return (string) ShopSettingStore::getMemberCodeFormat($projectId);
    }

    public function getMemberCodeLength(int $projectId): int
    {
        return (int) ShopSettingStore::getMemberCodeLength($projectId);
    }

    public function getMemberCodePrefix(int $projectId): string
    {
        return (string) ShopSettingStore::getMemberCodePrefix($projectId);
    }

    public function getMemberCodeNextNumber(int $projectId): int
    {
        return (int) ShopSettingStore::getMemberCodeNextNumber($projectId);
    }

    public function setMemberCodeNextNumber(int $nextNumber, int $projectId): void
    {
        ShopSettingStore::setMemberCodeNextNumber($nextNumber, $projectId);
    }

    // ── プロフィール ──

    public function getProfileFieldSettings(int $projectId): array
    {
        return ShopSettingStore::getProfileFieldSettings($projectId);
    }

    public function setProfileFieldSettings(array $settings, int $projectId): void
    {
        ShopSettingStore::setProfileFieldSettings($settings, $projectId);
    }

    public function defaultProfileFieldSettings(): array
    {
        return ShopSettingStore::defaultProfileFieldSettings();
    }

    public function getMemberRegistrationMode(int $projectId): string
    {
        return ShopSettingStore::getMemberRegistrationMode($projectId);
    }

    public function setMemberRegistrationMode(string $mode, int $projectId): void
    {
        ShopSettingStore::setMemberRegistrationMode($mode, $projectId);
    }

    // ── UI カスタマイズ ──

    public function getAdminSidebarColor(int $projectId): ?string
    {
        return ShopSettingStore::getAdminSidebarColor($projectId);
    }

    public function getAdminHeaderColor(int $projectId): ?string
    {
        return ShopSettingStore::getAdminHeaderColor($projectId);
    }

    public function getAdminPrimaryColor(int $projectId): ?string
    {
        return ShopSettingStore::getAdminPrimaryColor($projectId);
    }

    public function getStampDefaultImageUrl(int $projectId): ?string
    {
        $url = ShopSettingStore::getStampDefaultImageUrl($projectId);

        return $url !== null && $url !== '' ? (string) $url : null;
    }

    public function getCouponDefaultImageUrl(int $projectId): ?string
    {
        $url = ShopSettingStore::getCouponDefaultImageUrl($projectId);

        return $url !== null && $url !== '' ? (string) $url : null;
    }

    public function getFrontendLogoUrl(int $projectId): ?string
    {
        $url = ShopSettingStore::getFrontendLogoUrl($projectId);

        return $url !== null && $url !== '' ? (string) $url : null;
    }

    public function getFrontendHeaderText(int $projectId): ?string
    {
        return ShopSettingStore::getFrontendHeaderText($projectId);
    }

    // ── Wallet ──

    public function getWalletGoogleProgramLogoUrl(int $projectId): ?string
    {
        return ShopSettingStore::getWalletGoogleProgramLogoUrl($projectId);
    }

    public function setWalletGoogleProgramLogoUrl(?string $url, int $projectId): void
    {
        ShopSettingStore::setWalletGoogleProgramLogoUrl($url, $projectId);
    }

    public function getWalletGoogleHeroImageUrl(int $projectId): ?string
    {
        return ShopSettingStore::getWalletGoogleHeroImageUrl($projectId);
    }

    public function setWalletGoogleHeroImageUrl(?string $url, int $projectId): void
    {
        ShopSettingStore::setWalletGoogleHeroImageUrl($url, $projectId);
    }
}
