<?php

namespace App\Domain\Coupon\ValueObjects;

final class PendingIssuedCoupon
{
    public function __construct(
        public readonly int $projectId,
        public readonly int $memberId,
        public readonly int $couponId,
        public readonly string $couponNameSnapshot,
        public readonly \DateTimeInterface $issuedAt,
        public readonly \DateTimeInterface $validFrom,
        public readonly ?\DateTimeInterface $validUntil,
    ) {}
}
