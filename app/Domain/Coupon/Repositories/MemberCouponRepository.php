<?php

namespace App\Domain\Coupon\Repositories;

use App\Domain\Coupon\Aggregates\MemberCoupon;
use App\Domain\Coupon\ValueObjects\PendingIssuedCoupon;

interface MemberCouponRepository
{
    public function findByIdForMemberForUpdate(int $memberCouponId, int $memberId, int $projectId): ?MemberCoupon;

    public function save(MemberCoupon $memberCoupon): void;

    public function hasIssued(int $memberId, int $couponId, int $projectId): bool;

    public function hasIssuedGrantKey(int $memberId, string $grantKey, int $projectId): bool;

    public function hasIssuedInYear(int $memberId, int $couponId, int $year, int $projectId): bool;

    public function hasUnusedValid(int $memberId, int $couponId, \DateTimeInterface $now, int $projectId): bool;

    public function issue(PendingIssuedCoupon $pending): void;

    public function deleteUnusedForSource(int $projectId, int $memberId, string $sourceType, string $sourceId): int;
}
