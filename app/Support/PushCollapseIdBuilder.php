<?php

namespace App\Support;

class PushCollapseIdBuilder
{
    private const TYPE_ANNOUNCEMENT = 'announcement';

    private const TYPE_QUEUE_CALLED = 'queue_called';

    private const TYPE_COUPON_ISSUED = 'coupon_issued';

    public static function forAnnouncement(int $projectId, int $announcementId): string
    {
        return self::build($projectId, self::TYPE_ANNOUNCEMENT, $announcementId);
    }

    public static function forQueueCalled(int $projectId, int $userId): string
    {
        return self::build($projectId, self::TYPE_QUEUE_CALLED, $userId);
    }

    public static function forCouponIssued(int $projectId, int $memberCouponId): string
    {
        return self::build($projectId, self::TYPE_COUPON_ISSUED, $memberCouponId);
    }

    private static function build(int $projectId, string $type, int $entityId): string
    {
        return $projectId.':'.$type.':'.$entityId;
    }
}
