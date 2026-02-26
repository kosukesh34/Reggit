<?php

namespace App\Services\Admin;

use App\Models\Coupon;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\MemberStamp;
use App\Models\StampCard;
use App\Services\MemberPointsExpiryCalculator;
use App\Services\MemberRankCalculator;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MemberAdminService
{
    public function getPaginatedList(int $projectId, int $perPage = 20): LengthAwarePaginator
    {
        return Member::with('user')
            ->where('project_id', $projectId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function findOrFail(int $id, ?int $projectId = null): Member
    {
        $query = Member::with(['user', 'pointTransactions.order', 'memberStamps.stampCard', 'memberCoupons.coupon']);
        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        }
        $member = $query->findOrFail($id);

        $rankCalculator = app(MemberRankCalculator::class);
        $expiryCalculator = app(MemberPointsExpiryCalculator::class);
        $rankInfo = $rankCalculator->getRankInfo($member);

        $member->setAttribute('rank_label', $rankInfo['current_rank_label'] ?? $member->rank);
        $member->setAttribute('points_expiry', $expiryCalculator->getExpiryInfo($member));

        return $member;
    }

    /** 配布用の有効クーポン一覧（同一プロジェクト） */
    public function getActiveCouponsForSelect(int $projectId): Collection
    {
        return Coupon::where('project_id', $projectId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /** 配布用の有効スタンプカード一覧（同一プロジェクト） */
    public function getActiveStampCardsForSelect(int $projectId): Collection
    {
        return StampCard::where('project_id', $projectId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /** 会員にクーポンを1件発行（管理画面からの手動配布） */
    public function issueCouponToMember(Member $member, int $couponId, ?\DateTimeInterface $validFrom = null, ?\DateTimeInterface $validUntil = null): MemberCoupon
    {
        $coupon = Coupon::where('project_id', $member->project_id)->findOrFail($couponId);
        $validFrom = $validFrom ?? now();
        $validUntil = $validUntil ?? $coupon->end_at ?? Carbon::now()->addDays(30);

        return MemberCoupon::create([
            'project_id' => $member->project_id,
            'member_id' => $member->id,
            'coupon_id' => $coupon->id,
            'coupon_name_snapshot' => $coupon->name,
            'status' => MemberCoupon::STATUS_UNUSED,
            'issued_at' => now(),
            'valid_from' => $validFrom,
            'valid_until' => $validUntil instanceof \DateTimeInterface ? $validUntil : null,
        ]);
    }

    /** 会員のスタンプカードにスタンプを追加（管理画面からの手動付与） */
    public function addStampsToMember(Member $member, int $stampCardId, int $count = 1): MemberStamp
    {
        if ($count < 1) {
            throw new \InvalidArgumentException('スタンプ数は1以上を指定してください');
        }
        $stampCard = StampCard::where('project_id', $member->project_id)->findOrFail($stampCardId);
        $memberStamp = MemberStamp::firstOrCreate(
            [
                'project_id' => $member->project_id,
                'member_id' => $member->id,
                'stamp_card_id' => $stampCard->id,
            ],
            ['current_stamps' => 0]
        );
        if ($memberStamp->valid_until === null && $stampCard->valid_days !== null && (int) $stampCard->valid_days > 0) {
            $memberStamp->update(['valid_until' => Carbon::now()->addDays((int) $stampCard->valid_days)]);
        }
        $memberStamp->increment('current_stamps', $count);
        $memberStamp->refresh();
        $required = (int) $stampCard->required_stamps;
        if ($memberStamp->current_stamps >= $required && $memberStamp->completed_at === null) {
            $memberStamp->update(['completed_at' => now()]);
        }
        return $memberStamp->refresh();
    }
}
