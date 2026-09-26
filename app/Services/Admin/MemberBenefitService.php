<?php

namespace App\Services\Admin;

use App\Domain\Coupon\Repositories\MemberCouponRepository;
use App\Domain\Coupon\ValueObjects\PendingIssuedCoupon;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\MemberStamp;
use App\Models\StampCard;
use App\Services\Stamp\StampBalanceService;

class MemberBenefitService
{
    public function __construct(
        private MemberCouponRepository $memberCouponRepository,
        private StampBalanceService $stampBalanceService,
    ) {}

    /** 会員にクーポンを1件発行（管理画面からの手動配布） */
    public function issueCouponToMember(
        Member $member,
        int $couponId,
        ?\DateTimeInterface $validFrom = null,
        ?\DateTimeInterface $validUntil = null,
        ?string $issueSourceType = null,
        ?string $issueSourceId = null,
        ?string $issueGrantKey = null,
    ): MemberCoupon {
        $coupon = Coupon::where('project_id', $member->project_id)->findOrFail($couponId);
        $now = now();
        if ($coupon->end_at !== null && $coupon->end_at->lt($now)) {
            throw new \InvalidArgumentException('このクーポンは期限切れです');
        }
        $validFrom = $validFrom ?? $coupon->start_at ?? $now;
        $validUntil = $validUntil ?? $coupon->end_at;

        $this->memberCouponRepository->issue(new PendingIssuedCoupon(
            (int) $member->project_id,
            (int) $member->id,
            (int) $coupon->id,
            (string) $coupon->name,
            $now,
            $validFrom,
            $validUntil instanceof \DateTimeInterface ? $validUntil : null,
            $issueSourceType,
            $issueSourceId,
            $issueGrantKey,
        ));

        return MemberCoupon::query()
            ->where('member_id', $member->id)
            ->where('coupon_id', $coupon->id)
            ->orderByDesc('id')
            ->firstOrFail();
    }

    /**
     * 複数会員にクーポンを一括発行（プレゼント配信）。$memberIds が空の場合は全員に配布。
     *
     * @param  list<int>  $memberIds
     * @return array{count: int, member_ids: list<int>}
     */
    public function issueCouponToMembers(
        int $projectId,
        int $couponId,
        array $memberIds = [],
        ?string $issueSourceType = null,
        ?string $issueSourceId = null,
        bool $shouldNotify = true,
    ): array {
        $coupon = Coupon::where('project_id', $projectId)->findOrFail($couponId);
        $query = Member::where('project_id', $projectId);
        if ($memberIds !== []) {
            $query->whereIn('id', $memberIds);
        }
        $members = $query->get();
        if ($coupon->end_at !== null && $coupon->end_at->lt(now())) {
            throw new \InvalidArgumentException('このクーポンは期限切れです');
        }
        $validUntil = $coupon->end_at;
        $now = now();
        $issuedMemberIds = [];
        foreach ($members as $member) {
            $grantKey = null;
            if ($issueSourceType !== null && $issueSourceId !== null) {
                $grantKey = implode(':', [$issueSourceType, $issueSourceId, $couponId, (int) $member->id]);
                if ($this->memberCouponRepository->hasIssuedGrantKey((int) $member->id, $grantKey, $projectId)) {
                    continue;
                }
            } elseif ($this->memberCouponRepository->hasUnusedValid(
                (int) $member->id,
                $couponId,
                $now,
                $projectId,
            )) {
                continue;
            }
            $issue = function () use (
                $member,
                $couponId,
                $validUntil,
                $issueSourceType,
                $issueSourceId,
                $grantKey,
            ): void {
                $this->issueCouponToMember(
                    $member,
                    $couponId,
                    null,
                    $validUntil,
                    $issueSourceType,
                    $issueSourceId,
                    $grantKey,
                );
            };
            if ($shouldNotify) {
                $issue();
            } else {
                MemberCoupon::withoutEvents($issue);
            }
            $issuedMemberIds[] = (int) $member->id;
        }

        return [
            'count' => count($issuedMemberIds),
            'member_ids' => $issuedMemberIds,
        ];
    }

    /** 会員のスタンプカードにスタンプを追加（注文・管理画面からの付与） */
    public function addStampsToMember(
        Member $member,
        int $stampCardId,
        int $count = 1,
        ?string $description = null,
        ?int $orderId = null,
        ?string $issueSourceType = null,
        ?string $issueSourceId = null,
    ): MemberStamp {
        if ($count < 1) {
            throw new \InvalidArgumentException('スタンプ数は1以上を指定してください');
        }

        return $this->adjustStampsToMember(
            $member,
            $stampCardId,
            $count,
            $description ?? 'スタンプ付与',
            $orderId,
            $issueSourceType,
            $issueSourceId,
        );
    }

    /**
     * スタンプを加減算する。delta は 0 以外。残高は 0 未満にならない。
     */
    public function adjustStampsToMember(
        Member $member,
        int $stampCardId,
        int $delta,
        ?string $description = null,
        ?int $orderId = null,
        ?string $issueSourceType = null,
        ?string $issueSourceId = null,
    ): MemberStamp {
        if ($delta === 0) {
            throw new \InvalidArgumentException('スタンプ数は0以外を指定してください');
        }

        $stampCard = StampCard::where('project_id', $member->project_id)->findOrFail($stampCardId);

        return $this->stampBalanceService->adjust(
            $member,
            $stampCard,
            $delta,
            $description,
            $orderId,
            [],
            $issueSourceType,
            $issueSourceId,
        );
    }

    /**
     * 会員に発行済みの未使用クーポンを削除する。
     *
     * @throws \RuntimeException 使用済み・他会員のクーポンの場合
     */
    public function deleteMemberCoupon(Member $member, int $memberCouponId): void
    {
        $memberCoupon = MemberCoupon::query()
            ->where('project_id', $member->project_id)
            ->where('member_id', $member->id)
            ->whereKey($memberCouponId)
            ->firstOrFail();

        if ($memberCoupon->isUsed()) {
            throw new \RuntimeException('使用済みクーポンは削除できません');
        }

        $memberCoupon->delete();
    }
}
