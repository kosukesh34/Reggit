<?php

namespace App\Domain\Settings\Repositories;

interface CouponNotificationSettings extends LineConnectionSettings
{
    public function isFeatureEnabled(string $featureKey, int $projectId): bool;

    public function getCouponDefaultImageUrl(int $projectId): ?string;
}
