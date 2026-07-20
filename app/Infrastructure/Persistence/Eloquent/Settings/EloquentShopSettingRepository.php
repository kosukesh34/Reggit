<?php

namespace App\Infrastructure\Persistence\Eloquent\Settings;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Models\ShopSetting;

class EloquentShopSettingRepository implements ShopSettingRepository
{
    public function getOrderMode(int $projectId): string
    {
        return ShopSetting::getOrderMode($projectId);
    }

    public function getPointsValidDays(int $projectId): int
    {
        return ShopSetting::getPointsValidDays($projectId);
    }

    public function getPointsPerOrder(int $projectId): int
    {
        return (int) ShopSetting::getPointsPerOrder($projectId);
    }

    public function getPointEarnRate(int $projectId): int
    {
        return (int) ShopSetting::getPointEarnRate($projectId);
    }

    public function getStampEarnPerAmount(int $projectId): int
    {
        return (int) ShopSetting::getStampEarnPerAmount($projectId);
    }

    public function getOrderStampCardId(int $projectId): ?int
    {
        return ShopSetting::getOrderStampCardId($projectId);
    }

    public function getCouponDefaultImageUrl(int $projectId): ?string
    {
        $url = ShopSetting::getCouponDefaultImageUrl($projectId);

        return $url !== null && $url !== '' ? (string) $url : null;
    }

    public function getQueueAvgServeMinutes(int $projectId): int
    {
        return (int) ShopSetting::getQueueAvgServeMinutes($projectId);
    }

    public function getQueueRequireMembership(int $projectId): bool
    {
        return ShopSetting::getQueueRequireMembership($projectId);
    }

    public function getQueueChannelLine(int $projectId): bool
    {
        return ShopSetting::getQueueChannelLine($projectId);
    }

    public function getQueueChannelNative(int $projectId): bool
    {
        return ShopSetting::getQueueChannelNative($projectId);
    }

    public function getReservationTimeSlots(int $projectId): array
    {
        return ShopSetting::getReservationTimeSlots($projectId);
    }

    public function getAdvanceBookingDays(int $projectId): int
    {
        return ShopSetting::getAdvanceBookingDays($projectId);
    }

    public function getReservationCapacityPerSlot(int $projectId): int
    {
        return ShopSetting::getReservationCapacityPerSlot($projectId);
    }

    public function isDateAvailable(string $date, int $projectId): bool
    {
        return ShopSetting::isDateAvailable($date, $projectId);
    }

    public function getMemberRankSettings(int $projectId): array
    {
        return ShopSetting::getMemberRankSettings($projectId);
    }

    public function getRankBasis(int $projectId): string
    {
        return ShopSetting::getRankBasis($projectId);
    }

    public function getStampDefaultImageUrl(int $projectId): ?string
    {
        $url = ShopSetting::getStampDefaultImageUrl($projectId);

        return $url !== null && $url !== '' ? (string) $url : null;
    }

    public function getShowPointsDisplay(int $projectId): bool
    {
        return (bool) ShopSetting::getShowPointsDisplay($projectId);
    }

    public function getShowRankProgress(int $projectId): bool
    {
        return (bool) ShopSetting::getShowRankProgress($projectId);
    }

    public function getMemberCardDesign(int $projectId): string
    {
        return (string) ShopSetting::getMemberCardDesign($projectId);
    }

    public function getFrontendLogoUrl(int $projectId): ?string
    {
        $url = ShopSetting::getFrontendLogoUrl($projectId);

        return $url !== null && $url !== '' ? (string) $url : null;
    }

    public function getMemberCardDisplayType(int $projectId): string
    {
        return (string) ShopSetting::getMemberCardDisplayType($projectId);
    }

    public function getMemberCardCodeType(int $projectId): string
    {
        return (string) ShopSetting::getMemberCardCodeType($projectId);
    }

    public function getProfileFieldSettings(int $projectId): array
    {
        return ShopSetting::getProfileFieldSettings($projectId);
    }

    public function getMemberRegistrationMode(int $projectId): string
    {
        return ShopSetting::getMemberRegistrationMode($projectId);
    }

    public function getShowMemberCardForGuest(int $projectId): bool
    {
        return (bool) ShopSetting::getShowMemberCardForGuest($projectId);
    }

    public function getMemberCodeType(int $projectId): string
    {
        return (string) ShopSetting::getMemberCodeType($projectId);
    }

    public function getMemberCodeFormat(int $projectId): string
    {
        return (string) ShopSetting::getMemberCodeFormat($projectId);
    }

    public function getMemberCodeLength(int $projectId): int
    {
        return (int) ShopSetting::getMemberCodeLength($projectId);
    }

    public function getMemberCodePrefix(int $projectId): string
    {
        return (string) ShopSetting::getMemberCodePrefix($projectId);
    }

    public function getMemberCodeNextNumber(int $projectId): int
    {
        return (int) ShopSetting::getMemberCodeNextNumber($projectId);
    }

    public function setMemberCodeNextNumber(int $nextNumber, int $projectId): void
    {
        ShopSetting::setMemberCodeNextNumber($nextNumber, $projectId);
    }

    public function isFeatureEnabled(string $featureKey, int $projectId): bool
    {
        return ShopSetting::isFeatureEnabled($featureKey, $projectId);
    }

    public function getValue(string $key, mixed $default, int $projectId): mixed
    {
        return ShopSetting::getValue($key, $default, $projectId);
    }

    public function getFeatureFlags(int $projectId): array
    {
        return ShopSetting::getFeatureFlags($projectId);
    }

    public function getFeatureFlagLabels(): array
    {
        return ShopSetting::featureFlagLabels();
    }

    public function getAdminSidebarColor(int $projectId): string
    {
        return (string) (ShopSetting::getAdminSidebarColor($projectId) ?? '');
    }

    public function getAdminHeaderColor(int $projectId): string
    {
        return (string) (ShopSetting::getAdminHeaderColor($projectId) ?? '');
    }

    public function getAdminPrimaryColor(int $projectId): string
    {
        return (string) (ShopSetting::getAdminPrimaryColor($projectId) ?? '');
    }

    public function getFrontendHeaderText(int $projectId): string
    {
        return (string) (ShopSetting::getFrontendHeaderText($projectId) ?? '');
    }

    public function getWalletGoogleProgramLogoUrl(int $projectId): ?string
    {
        $url = ShopSetting::getWalletGoogleProgramLogoUrl($projectId);

        return $url !== null && $url !== '' ? (string) $url : null;
    }

    public function getWalletGoogleHeroImageUrl(int $projectId): ?string
    {
        $url = ShopSetting::getWalletGoogleHeroImageUrl($projectId);

        return $url !== null && $url !== '' ? (string) $url : null;
    }

    public function defaultFrontendMenuItems(): array
    {
        return ShopSetting::defaultFrontendMenuItems();
    }

    public function setValue(string $key, mixed $value, string $type, int $projectId): void
    {
        ShopSetting::setValue($key, $value, $type, $projectId);
    }

    public function setFeatureFlags(array $flags, int $projectId): void
    {
        ShopSetting::setFeatureFlags($flags, $projectId);
    }

    public function setFrontendMenuItems(array $items, int $projectId): void
    {
        ShopSetting::setFrontendMenuItems($items, $projectId);
    }

    public function setMemberRankSettings(array $settings, int $projectId): void
    {
        ShopSetting::setMemberRankSettings($settings, $projectId);
    }

    public function setProfileFieldSettings(array $settings, int $projectId): void
    {
        ShopSetting::setProfileFieldSettings($settings, $projectId);
    }

    public function setMemberRegistrationMode(string $mode, int $projectId): void
    {
        ShopSetting::setMemberRegistrationMode($mode, $projectId);
    }

    public function setShowMemberCardForGuest(bool $value, int $projectId): void
    {
        ShopSetting::setShowMemberCardForGuest($value, $projectId);
    }

    public function setMemberCardDesign(string $value, int $projectId): void
    {
        ShopSetting::setMemberCardDesign($value, $projectId);
    }

    public function setMemberCardDisplayType(string $value, int $projectId): void
    {
        ShopSetting::setMemberCardDisplayType($value, $projectId);
    }

    public function setMemberCardCodeType(string $value, int $projectId): void
    {
        ShopSetting::setMemberCardCodeType($value, $projectId);
    }

    public function setWalletGoogleProgramLogoUrl(?string $url, int $projectId): void
    {
        ShopSetting::setWalletGoogleProgramLogoUrl($url, $projectId);
    }

    public function setWalletGoogleHeroImageUrl(?string $url, int $projectId): void
    {
        ShopSetting::setWalletGoogleHeroImageUrl($url, $projectId);
    }

    public function setLineLiffId(?string $liffId, int $projectId): void
    {
        ShopSetting::setLineLiffId($liffId, $projectId);
    }

    public function setLineLiffUrl(?string $liffUrl, int $projectId): void
    {
        ShopSetting::setLineLiffUrl($liffUrl, $projectId);
    }
}
