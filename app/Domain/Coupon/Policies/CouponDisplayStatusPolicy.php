<?php

namespace App\Domain\Coupon\Policies;

final class CouponDisplayStatusPolicy
{
    public const AVAILABLE = 'available';

    public const UPCOMING = 'upcoming';

    public const USED = 'used';

    public const EXPIRED = 'expired';

    /** 有効期間内だが、利用できる曜日・時間帯の外 */
    public const OUTSIDE_USAGE_TIME = 'outside_usage_time';

    public function resolve(
        string $status,
        ?\DateTimeInterface $validFrom,
        ?\DateTimeInterface $validUntil,
        \DateTimeInterface $now,
    ): string {
        if ($status === 'used') {
            return self::USED;
        }

        if ($validFrom !== null && $validFrom > $now) {
            return self::UPCOMING;
        }

        if ($validUntil !== null && $validUntil < $now) {
            return self::EXPIRED;
        }

        return self::AVAILABLE;
    }
}
