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
}
