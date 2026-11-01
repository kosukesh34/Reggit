<?php

namespace App\Infrastructure\Persistence\Eloquent\Settings;

use App\Domain\Reservation\ValueObjects\ReservationReminderConfig;
use App\Domain\Reservation\ValueObjects\ReservationRules;
use App\Domain\Settings\Repositories\AdminAppearanceSettings;
use App\Domain\Settings\Repositories\AppConfigSettings;
use App\Domain\Settings\Repositories\BusinessHoursSettings;
use App\Domain\Settings\Repositories\CouponNotificationSettings;
use App\Domain\Settings\Repositories\FeatureSettings;
use App\Domain\Settings\Repositories\FrontendShellSettings;
use App\Domain\Settings\Repositories\HomeSettingsRepository;
use App\Domain\Settings\Repositories\KitchenAccessSettings;
use App\Domain\Settings\Repositories\LiffDesignerSettings;
use App\Domain\Settings\Repositories\LineAdminSettings;
use App\Domain\Settings\Repositories\LineConnectionSettings;
use App\Domain\Settings\Repositories\MemberCardPreviewSettings;
use App\Domain\Settings\Repositories\MemberCardSettings;
use App\Domain\Settings\Repositories\MemberCodeSettings;
use App\Domain\Settings\Repositories\MemberSettings;
use App\Domain\Settings\Repositories\OperationalSettings;
use App\Domain\Settings\Repositories\OrderNotificationSettings;
use App\Domain\Settings\Repositories\OrderSettings;
use App\Domain\Settings\Repositories\ProfileSettings;
use App\Domain\Settings\Repositories\ProjectSettings;
use App\Domain\Settings\Repositories\QueueAdminSettings;
use App\Domain\Settings\Repositories\QueueSettings;
use App\Domain\Settings\Repositories\RankSettings;
use App\Domain\Settings\Repositories\ReservationSettings;
use App\Domain\Settings\Repositories\SmaregiSettings;
use App\Domain\Settings\Repositories\StampSettings;
use App\Domain\Settings\Repositories\WalletSettings;
use App\Domain\Settings\ShopSettingKeys;
use App\Models\Settings\ProjectBusinessSetting;
use App\Models\Store;

class EloquentShopSettingRepository implements AdminAppearanceSettings, AppConfigSettings, BusinessHoursSettings, CouponNotificationSettings, FeatureSettings, FrontendShellSettings, HomeSettingsRepository, KitchenAccessSettings, LiffDesignerSettings, LineAdminSettings, LineConnectionSettings, MemberCardPreviewSettings, MemberCardSettings, MemberCodeSettings, MemberSettings, OperationalSettings, OrderNotificationSettings, OrderSettings, ProfileSettings, ProjectSettings, QueueAdminSettings, QueueSettings, RankSettings, ReservationSettings, SmaregiSettings, StampSettings, WalletSettings
{
    public function __construct(
        private ShopSettingStore $values,
        private BusinessSettingStore $businessSettingStore,
        private OrderSettingStore $orderSettingStore,
        private LineSettingStore $lineSettingStore,
        private LiffSettingStore $liffSettingStore,
        private FeatureFlagStore $featureFlagStore,
        private MemberRankSettingStore $memberRankSettingStore,
        private MemberCardSettingStore $memberCardSettingStore,
        private MemberCodeSettingStore $memberCodeSettingStore,
        private ProfileSettingStore $profileSettingStore,
        private AppearanceSettingStore $appearanceSettingStore,
    ) {}

    public function getValue(string $key, mixed $default, int $projectId): mixed
    {
        return $this->values->getValue($key, $default, $projectId);
    }

    public function setValue(string $key, mixed $value, string $type, int $projectId): void
    {
        $this->values->setValue($key, $value, $type, $projectId);
    }

    // ── ビジネス設定 ──

    public function getBusinessHours(int $projectId): array
    {
        return $this->businessSettingStore->getBusinessHours($projectId);
    }

    public function getReservationTimeSlots(int $projectId): array
    {
        return $this->businessSettingStore->getReservationTimeSlots($projectId);
    }

    public function getClosedDays(int $projectId): array
    {
        return $this->businessSettingStore->getClosedDays($projectId);
    }

    public function getClosedDates(int $projectId): array
    {
        return $this->businessSettingStore->getClosedDates($projectId);
    }

    public function getAdvanceBookingDays(int $projectId): int
    {
        return $this->businessSettingStore->getAdvanceBookingDays($projectId);
    }

    public function getReservationCapacityPerSlot(int $projectId): int
    {
        return $this->businessSettingStore->getReservationCapacityPerSlot($projectId);
    }

    public function getReservationRules(int $projectId): ReservationRules
    {
        return new ReservationRules(
            $this->businessSettingStore->getReservationTimeSlots($projectId),
            $this->businessSettingStore->getAdvanceBookingDays($projectId),
            $this->businessSettingStore->getReservationCapacityPerSlot($projectId),
            (bool) $this->values->getValue('reservation_capacity_by_people', false, $projectId),
            (int) $this->values->getValue('reservation_max_party_size', 10, $projectId),
            (bool) $this->values->getValue('reservation_same_day', false, $projectId),
            (int) $this->values->getValue('reservation_lead_minutes', 0, $projectId),
            (int) $this->values->getValue('reservation_change_deadline_hours', 0, $projectId),
        );
    }

    private const REMINDER_KEYS = ['reservation_reminder_hours', 'reservation_reminder_second_hours', 'reservation_reminder_message'];

    public function getReservationReminderConfig(int $projectId): ReservationReminderConfig
    {
        return self::reminderConfig(array_map(fn (string $key) => $this->values->getValue($key, null, $projectId), array_combine(self::REMINDER_KEYS, self::REMINDER_KEYS)));
    }

    public function getReservationReminderConfigsByStore(int $projectId): array
    {
        $project = $this->getReservationReminderConfig($projectId);
        $base = ['reservation_reminder_hours' => $project->firstHours, 'reservation_reminder_second_hours' => $project->secondHours, 'reservation_reminder_message' => $project->message];

        return Store::withoutGlobalScopes()->where('project_id', $projectId)->get(['id', 'settings'])
            ->filter(fn (Store $store): bool => array_intersect_key($store->settings ?? [], $base) !== [])
            ->mapWithKeys(fn (Store $store): array => [(int) $store->id => self::reminderConfig([...$base, ...array_intersect_key($store->settings ?? [], $base)])])
            ->all();
    }

    public function getProjectIdsWithReservationReminders(): array
    {
        $projects = ProjectBusinessSetting::query()
            ->where(fn ($query) => $query->where('reservation_reminder_hours', '>', 0)->orWhere('reservation_reminder_second_hours', '>', 0))
            ->pluck('project_id');
        $stores = Store::withoutGlobalScopes()
            ->where(fn ($query) => $query->where('settings->reservation_reminder_hours', '>', 0)->orWhere('settings->reservation_reminder_second_hours', '>', 0))
            ->pluck('project_id');

        return $projects->merge($stores)->map(fn ($id): int => (int) $id)->unique()->values()->all();
    }

    /** @param array<string, mixed> $values */
    private static function reminderConfig(array $values): ReservationReminderConfig
    {
        $message = trim((string) ($values['reservation_reminder_message'] ?? ''));

        return new ReservationReminderConfig((int) ($values['reservation_reminder_hours'] ?? 0), (int) ($values['reservation_reminder_second_hours'] ?? 0), $message !== '' ? $message : null);
    }

    public function isDateAvailable(string $date, int $projectId): bool
    {
        return $this->businessSettingStore->isDateAvailable($date, $projectId);
    }

    // ── 順番待ち ──

    public function getQueueAvgServeMinutes(int $projectId): int
    {
        return (int) $this->businessSettingStore->getQueueAvgServeMinutes($projectId);
    }

    public function getQueueRequireMembership(int $projectId): bool
    {
        return $this->businessSettingStore->getQueueRequireMembership($projectId);
    }

    public function getQueueChannelLine(int $projectId): bool
    {
        return $this->businessSettingStore->getQueueChannelLine($projectId);
    }

    public function getQueueChannelNative(int $projectId): bool
    {
        return $this->businessSettingStore->getQueueChannelNative($projectId);
    }

    // ── オーダー ──

    public function getOrderMode(int $projectId): string
    {
        return $this->orderSettingStore->getOrderMode($projectId);
    }

    public function getShopShippingFee(int $projectId): int
    {
        return max(0, (int) $this->values->getValue('shop_shipping_fee', 500, $projectId));
    }

    public function getShopShippingFeesByPrefecture(int $projectId): array
    {
        $raw = $this->values->getValue('shop_shipping_fees_by_prefecture', [], $projectId);
        if (! is_array($raw)) {
            return [];
        }
        $fees = [];
        foreach ($raw as $prefecture => $fee) {
            if (! is_string($prefecture) || ! is_numeric($fee)) {
                continue;
            }
            $fees[$prefecture] = max(0, (int) $fee);
        }

        return $fees;
    }

    public function getShopShippingFeeForPrefecture(int $projectId, string $prefecture): int
    {
        $fees = $this->getShopShippingFeesByPrefecture($projectId);
        if (array_key_exists($prefecture, $fees)) {
            return $fees[$prefecture];
        }

        return $this->getShopShippingFee($projectId);
    }

    public function setOrderMode(string $mode, int $projectId): void
    {
        $this->orderSettingStore->setOrderMode($mode, $projectId);
    }

    public function isOrderLineNotifyEnabled(int $projectId): bool
    {
        return $this->orderSettingStore->isOrderLineNotifyEnabled($projectId);
    }

    public function setOrderLineNotifyEnabled(bool $enabled, int $projectId): void
    {
        $this->orderSettingStore->setOrderLineNotifyEnabled($enabled, $projectId);
    }

    public function isOrderReadyLineNotifyEnabled(int $projectId): bool
    {
        return $this->orderSettingStore->isOrderReadyLineNotifyEnabled($projectId);
    }

    public function setOrderReadyLineNotifyEnabled(bool $enabled, int $projectId): void
    {
        $this->orderSettingStore->setOrderReadyLineNotifyEnabled($enabled, $projectId);
    }

    public function getKitchenAccessToken(int $projectId): string
    {
        return $this->orderSettingStore->getKitchenAccessToken($projectId);
    }

    public function setKitchenAccessToken(?string $token, int $projectId): void
    {
        $this->orderSettingStore->setKitchenAccessToken($token, $projectId);
    }

    // ── LINE / LIFF ──

    public function getLineLiffId(int $projectId): string
    {
        return $this->lineSettingStore->getLineLiffId($projectId);
    }

    public function setLineLiffId(?string $liffId, int $projectId): void
    {
        $this->lineSettingStore->setLineLiffId($liffId, $projectId);
    }

    public function getLineLiffUrl(int $projectId): string
    {
        return $this->lineSettingStore->getLineLiffUrl($projectId);
    }

    public function setLineLiffUrl(?string $liffUrl, int $projectId): void
    {
        $this->lineSettingStore->setLineLiffUrl($liffUrl, $projectId);
    }

    public function getLineChannelAccessToken(int $projectId): string
    {
        return $this->lineSettingStore->getLineChannelAccessToken($projectId);
    }

    public function setLineChannelAccessToken(?string $token, int $projectId): void
    {
        $this->lineSettingStore->setLineChannelAccessToken($token, $projectId);
    }

    public function hasLineChannelAccessToken(int $projectId): bool
    {
        return $this->lineSettingStore->hasLineChannelAccessToken($projectId);
    }

    public function getLineChannelSecret(int $projectId): string
    {
        return $this->lineSettingStore->getLineChannelSecret($projectId);
    }

    public function setLineChannelSecret(?string $secret, int $projectId): void
    {
        $this->lineSettingStore->setLineChannelSecret($secret, $projectId);
    }

    public function getLineFriendAddUrl(int $projectId): string
    {
        return $this->lineSettingStore->getLineFriendAddUrl($projectId);
    }

    public function setLineFriendAddUrl(?string $url, int $projectId): void
    {
        $this->lineSettingStore->setLineFriendAddUrl($url, $projectId);
    }

    public function getLineThemeColors(int $projectId): array
    {
        return $this->lineSettingStore->getLineThemeColors($projectId);
    }

    // ── LIFF デザイナー ──

    public function getLiffTabLayouts(int $projectId): array
    {
        return $this->liffSettingStore->getLiffTabLayouts($projectId);
    }

    public function setLiffTabLayouts(array $layouts, int $projectId): void
    {
        $this->liffSettingStore->setLiffTabLayouts($layouts, $projectId);
    }

    public function getLiffDetailLayouts(int $projectId): array
    {
        return $this->liffSettingStore->getLiffDetailLayouts($projectId);
    }

    public function setLiffDetailLayouts(array $layouts, int $projectId): void
    {
        $this->liffSettingStore->setLiffDetailLayouts($layouts, $projectId);
    }

    public function getLiffCustomCss(int $projectId): string
    {
        return $this->liffSettingStore->getLiffCustomCss($projectId);
    }

    public function setLiffCustomCss(string $css, int $projectId): void
    {
        $this->liffSettingStore->setLiffCustomCss($css, $projectId);
    }

    public function getLiffTabSettings(int $projectId): array
    {
        return $this->liffSettingStore->getLiffTabSettings($projectId);
    }

    public function setLiffTabSettings(array $settings, int $projectId): void
    {
        $this->liffSettingStore->setLiffTabSettings($settings, $projectId);
    }

    public function getLiffSavedScreens(int $projectId): array
    {
        return $this->liffSettingStore->getLiffSavedScreens($projectId);
    }

    public function setLiffSavedScreens(array $screens, int $projectId): void
    {
        $this->liffSettingStore->setLiffSavedScreens($screens, $projectId);
    }

    public function liffSavedScreensMax(): int
    {
        return ShopSettingKeys::LIFF_SAVED_SCREENS_MAX;
    }

    public function defaultLiffDesignerTemplates(): array
    {
        return $this->liffSettingStore->defaultLiffDesignerTemplates();
    }

    public function defaultLiffTabLayouts(): array
    {
        return $this->liffSettingStore->defaultLiffTabLayouts();
    }

    public function defaultLiffDetailLayouts(): array
    {
        return $this->liffSettingStore->defaultLiffDetailLayouts();
    }

    public function defaultLiffTabSettings(): array
    {
        return $this->liffSettingStore->defaultLiffTabSettings();
    }

    public function ensureLiffTabLayouts(array $layouts): array
    {
        return $this->liffSettingStore->ensureLiffTabLayouts($layouts);
    }

    // ── フロントメニュー ──

    public function defaultFrontendMenuItems(): array
    {
        return $this->liffSettingStore->defaultFrontendMenuItems();
    }

    public function getFrontendMenuItems(int $projectId): array
    {
        return $this->liffSettingStore->getFrontendMenuItems($projectId);
    }

    public function setFrontendMenuItems(array $items, int $projectId): void
    {
        $this->liffSettingStore->setFrontendMenuItems($items, $projectId);
    }

    public function normalizeFrontendMenuIcon(string $tab, string $icon): string
    {
        return $this->liffSettingStore->normalizeFrontendMenuIcon($tab, $icon);
    }

    // ── 機能フラグ ──

    public function getFeatureFlags(int $projectId): array
    {
        return $this->featureFlagStore->getFeatureFlags($projectId);
    }

    public function setFeatureFlags(array $flags, int $projectId): void
    {
        $this->featureFlagStore->setFeatureFlags($flags, $projectId);
    }

    public function isFeatureEnabled(string $featureKey, int $projectId): bool
    {
        return $this->featureFlagStore->isFeatureEnabled($featureKey, $projectId);
    }

    public function getFeatureFlagLabels(): array
    {
        return $this->featureFlagStore->featureFlagLabels();
    }

    public function withPreservedLotteryManagedFlags(array $flags, int $projectId): array
    {
        return $this->featureFlagStore->withPreservedLotteryManagedFlags($flags, $projectId);
    }

    // ── ポイント・スタンプ ──

    public function getPointEarnRate(int $projectId): int
    {
        return (int) $this->memberRankSettingStore->getPointEarnRate($projectId);
    }

    public function getPointEarnRateForRank(int $projectId, ?string $rankKey): int
    {
        return (int) $this->memberRankSettingStore->getPointEarnRateForRank($projectId, $rankKey);
    }

    public function getPointsPerOrder(int $projectId): int
    {
        return (int) $this->memberRankSettingStore->getPointsPerOrder($projectId);
    }

    public function getPointsValidDays(int $projectId): int
    {
        return $this->memberRankSettingStore->getPointsValidDays($projectId);
    }

    public function getStampEarnPerAmount(int $projectId): int
    {
        return (int) $this->memberRankSettingStore->getStampEarnPerAmount($projectId);
    }

    public function getStampEarnPerAmountForRank(int $projectId, ?string $rankKey): int
    {
        return (int) $this->memberRankSettingStore->getStampEarnPerAmountForRank($projectId, $rankKey);
    }

    public function getOrderStampCardId(int $projectId): ?int
    {
        return $this->memberRankSettingStore->getOrderStampCardId($projectId);
    }

    public function getUseStamps(int $projectId): bool
    {
        return $this->memberRankSettingStore->getUseStamps($projectId);
    }

    public function getUseTransactionStamps(int $projectId): bool
    {
        return $this->memberRankSettingStore->getUseTransactionStamps($projectId);
    }

    public function getUsePoints(int $projectId): bool
    {
        return $this->memberRankSettingStore->getUsePoints($projectId);
    }

    // ── 会員ランク ──

    public function getMemberRankSettings(int $projectId): array
    {
        return $this->memberRankSettingStore->getMemberRankSettings($projectId);
    }

    public function setMemberRankSettings(array $settings, int $projectId): void
    {
        $this->memberRankSettingStore->setMemberRankSettings($settings, $projectId);
    }

    public function getRankBasis(int $projectId): string
    {
        return $this->memberRankSettingStore->getRankBasis($projectId);
    }

    public function getRankAggregationType(int $projectId): string
    {
        return $this->memberRankSettingStore->getRankAggregationType($projectId);
    }

    public function getRankPeriodDays(int $projectId): int
    {
        return $this->memberRankSettingStore->getRankPeriodDays($projectId);
    }

    public function getIsRankDownEnabled(int $projectId): bool
    {
        return $this->memberRankSettingStore->getIsRankDownEnabled($projectId);
    }

    public function defaultMemberRankSettings(): array
    {
        return $this->memberRankSettingStore->defaultMemberRankSettings();
    }

    // ── 会員証 ──

    public function getShowPointsDisplay(int $projectId): bool
    {
        return (bool) $this->memberCardSettingStore->getShowPointsDisplay($projectId);
    }

    public function getShowRankProgress(int $projectId): bool
    {
        return (bool) $this->memberCardSettingStore->getShowRankProgress($projectId);
    }

    public function getShowMemberCardForGuest(int $projectId): bool
    {
        return (bool) $this->memberCardSettingStore->getShowMemberCardForGuest($projectId);
    }

    public function setShowMemberCardForGuest(bool $value, int $projectId): void
    {
        $this->memberCardSettingStore->setShowMemberCardForGuest($value, $projectId);
    }

    public function getMemberCardDesign(int $projectId): string
    {
        return (string) $this->memberCardSettingStore->getMemberCardDesign($projectId);
    }

    public function setMemberCardDesign(string $value, int $projectId): void
    {
        $this->memberCardSettingStore->setMemberCardDesign($value, $projectId);
    }

    public function getMemberCardDisplayType(int $projectId): string
    {
        return (string) $this->memberCardSettingStore->getMemberCardDisplayType($projectId);
    }

    public function setMemberCardDisplayType(string $value, int $projectId): void
    {
        $this->memberCardSettingStore->setMemberCardDisplayType($value, $projectId);
    }

    public function getMemberCardCodeType(int $projectId): string
    {
        return (string) $this->memberCardSettingStore->getMemberCardCodeType($projectId);
    }

    public function setMemberCardCodeType(string $value, int $projectId): void
    {
        $this->memberCardSettingStore->setMemberCardCodeType($value, $projectId);
    }

    // ── 会員コード ──

    public function getMemberCodeType(int $projectId): string
    {
        return (string) $this->memberCodeSettingStore->getMemberCodeType($projectId);
    }

    public function getMemberCodeFormat(int $projectId): string
    {
        return (string) $this->memberCodeSettingStore->getMemberCodeFormat($projectId);
    }

    public function getMemberCodeLength(int $projectId): int
    {
        return (int) $this->memberCodeSettingStore->getMemberCodeLength($projectId);
    }

    public function getMemberCodePrefix(int $projectId): string
    {
        return (string) $this->memberCodeSettingStore->getMemberCodePrefix($projectId);
    }

    public function getMemberCodeNextNumber(int $projectId): int
    {
        return (int) $this->memberCodeSettingStore->getMemberCodeNextNumber($projectId);
    }

    public function setMemberCodeNextNumber(int $nextNumber, int $projectId): void
    {
        $this->memberCodeSettingStore->setMemberCodeNextNumber($nextNumber, $projectId);
    }

    // ── プロフィール ──

    public function getProfileFieldSettings(int $projectId): array
    {
        return $this->profileSettingStore->getProfileFieldSettings($projectId);
    }

    public function setProfileFieldSettings(array $settings, int $projectId): void
    {
        $this->profileSettingStore->setProfileFieldSettings($settings, $projectId);
    }

    public function defaultProfileFieldSettings(): array
    {
        return $this->profileSettingStore->defaultProfileFieldSettings();
    }

    public function getMemberRegistrationMode(int $projectId): string
    {
        return $this->profileSettingStore->getMemberRegistrationMode($projectId);
    }

    public function setMemberRegistrationMode(string $mode, int $projectId): void
    {
        $this->profileSettingStore->setMemberRegistrationMode($mode, $projectId);
    }

    // ── UI カスタマイズ ──

    public function getAdminSidebarColor(int $projectId): ?string
    {
        return $this->appearanceSettingStore->getAdminSidebarColor($projectId);
    }

    public function getAdminHeaderColor(int $projectId): ?string
    {
        return $this->appearanceSettingStore->getAdminHeaderColor($projectId);
    }

    public function getAdminPrimaryColor(int $projectId): ?string
    {
        return $this->appearanceSettingStore->getAdminPrimaryColor($projectId);
    }

    public function getStampDefaultImageUrl(int $projectId): ?string
    {
        $url = $this->appearanceSettingStore->getStampDefaultImageUrl($projectId);

        return $url !== null && $url !== '' ? (string) $url : null;
    }

    public function getCouponDefaultImageUrl(int $projectId): ?string
    {
        $url = $this->appearanceSettingStore->getCouponDefaultImageUrl($projectId);

        return $url !== null && $url !== '' ? (string) $url : null;
    }

    public function getFrontendLogoUrl(int $projectId): ?string
    {
        $url = $this->appearanceSettingStore->getFrontendLogoUrl($projectId);

        return $url !== null && $url !== '' ? (string) $url : null;
    }

    public function getFrontendHeaderText(int $projectId): ?string
    {
        return $this->appearanceSettingStore->getFrontendHeaderText($projectId);
    }

    // ── Wallet ──

    public function getWalletGoogleProgramLogoUrl(int $projectId): ?string
    {
        return $this->appearanceSettingStore->getWalletGoogleProgramLogoUrl($projectId);
    }

    public function setWalletGoogleProgramLogoUrl(?string $url, int $projectId): void
    {
        $this->appearanceSettingStore->setWalletGoogleProgramLogoUrl($url, $projectId);
    }

    public function getWalletGoogleHeroImageUrl(int $projectId): ?string
    {
        return $this->appearanceSettingStore->getWalletGoogleHeroImageUrl($projectId);
    }

    public function setWalletGoogleHeroImageUrl(?string $url, int $projectId): void
    {
        $this->appearanceSettingStore->setWalletGoogleHeroImageUrl($url, $projectId);
    }
}
