<?php

namespace App\Infrastructure\Persistence\Eloquent\Coupon;

use App\Domain\Coupon\Aggregates\MemberCoupon as MemberCouponAggregate;
use App\Domain\Coupon\Repositories\MemberCouponRepository;
use App\Domain\Coupon\ValueObjects\PendingIssuedCoupon;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\MemberCoupon as MemberCouponModel;
use App\Models\Scopes\ProjectScope;

class EloquentMemberCouponRepository implements MemberCouponRepository
{
    public function findByIdForMemberForUpdate(int $memberCouponId, int $memberId, int $projectId): ?MemberCouponAggregate
    {
        $model = MemberCouponModel::query()
            ->withoutGlobalScope(ProjectScope::class)
            ->where('project_id', $projectId)
            ->where('member_id', $memberId)
            ->where('id', $memberCouponId)
            ->lockForUpdate()
            ->with('coupon')
            ->first();

        if ($model === null) {
            return null;
        }

        return MemberCouponMapper::toDomain($model);
    }

    public function save(MemberCouponAggregate $memberCoupon): void
    {
        if ($memberCoupon->wasMarkedUsed() === false) {
            return;
        }

        $model = MemberCouponModel::query()
            ->withoutGlobalScope(ProjectScope::class)
            ->whereKey($memberCoupon->id()->value())
            ->where('project_id', $memberCoupon->projectId())
            ->first();
        if ($model === null) {
            throw new \RuntimeException('MemberCoupon not found for save: '.$memberCoupon->id()->value());
        }

        $usedAt = $memberCoupon->usedAt();
        $model->update([
            'status' => MemberCouponAggregate::STATUS_USED,
            'used_at' => $usedAt,
        ]);

        CouponRedemption::create([
            'project_id' => $memberCoupon->projectId(),
            'coupon_id' => $memberCoupon->couponId(),
            'member_id' => $memberCoupon->memberId(),
            'member_coupon_id' => $memberCoupon->id()->value(),
            'order_id' => null,
            'used_at' => $usedAt,
        ]);

        Coupon::query()
            ->withoutGlobalScope(ProjectScope::class)
            ->whereKey($memberCoupon->couponId())
            ->where('project_id', $memberCoupon->projectId())
            ->increment('used_count');
    }

    public function hasIssued(int $memberId, int $couponId, int $projectId): bool
    {
        return MemberCouponModel::query()
            ->withoutGlobalScope(ProjectScope::class)
            ->where('project_id', $projectId)
            ->where('member_id', $memberId)
            ->where('coupon_id', $couponId)
            ->exists();
    }

    public function hasIssuedInYear(int $memberId, int $couponId, int $year, int $projectId): bool
    {
        return MemberCouponModel::query()
            ->withoutGlobalScope(ProjectScope::class)
            ->where('project_id', $projectId)
            ->where('member_id', $memberId)
            ->where('coupon_id', $couponId)
            ->whereYear('issued_at', $year)
            ->exists();
    }

    public function hasUnusedValid(int $memberId, int $couponId, \DateTimeInterface $now, int $projectId): bool
    {
        return MemberCouponModel::query()
            ->withoutGlobalScope(ProjectScope::class)
            ->where('project_id', $projectId)
            ->where('member_id', $memberId)
            ->where('coupon_id', $couponId)
            ->where('status', MemberCouponAggregate::STATUS_UNUSED)
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', $now);
            })
            ->exists();
    }

    public function issue(PendingIssuedCoupon $pending): void
    {
        MemberCouponModel::create([
            'project_id' => $pending->projectId,
            'member_id' => $pending->memberId,
            'coupon_id' => $pending->couponId,
            'coupon_name_snapshot' => $pending->couponNameSnapshot,
            'status' => MemberCouponAggregate::STATUS_UNUSED,
            'issued_at' => $pending->issuedAt,
            'valid_from' => $pending->validFrom,
            'valid_until' => $pending->validUntil,
            'issue_source_type' => $pending->issueSourceType,
            'issue_source_id' => $pending->issueSourceId,
            'issue_grant_key' => $pending->issueGrantKey,
        ]);
    }

    public function hasIssuedGrantKey(int $memberId, string $grantKey, int $projectId): bool
    {
        return MemberCouponModel::query()
            ->withoutGlobalScope(ProjectScope::class)
            ->where('project_id', $projectId)
            ->where('member_id', $memberId)
            ->where('issue_grant_key', $grantKey)
            ->exists();
    }

    public function deleteUnusedForSource(int $projectId, int $memberId, string $sourceType, string $sourceId): int
    {
        $issued = MemberCouponModel::query()
            ->withoutGlobalScope(ProjectScope::class)
            ->where('project_id', $projectId)
            ->where('member_id', $memberId)
            ->where('issue_source_type', $sourceType)
            ->where('issue_source_id', $sourceId)
            ->lockForUpdate()
            ->get();

        $deleted = 0;
        foreach ($issued as $memberCoupon) {
            if ($memberCoupon->isUsed()) {
                continue;
            }
            CouponRedemption::query()
                ->where('member_coupon_id', (int) $memberCoupon->id)
                ->delete();
            $memberCoupon->delete();
            $deleted++;
        }

        return $deleted;
    }
}
