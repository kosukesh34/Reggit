<?php

namespace App\Services\Admin;

use App\Enums\MemberCouponSource;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Services\AnnouncementStoreAudience;
use App\Support\MemberChannel;
use Illuminate\Support\Collection;

/** お知らせの配信対象会員の決定（予約配信は送信時に確定）、配信クーポンの発行、配信先一覧。 */
class AnnouncementAudienceService
{
    public function __construct(
        private MemberSegmentService $memberSegmentService,
        private MemberAdminService $memberAdminService,
        private MemberBenefitService $memberBenefitService,
        private readonly AnnouncementStoreAudience $storeAudience,
    ) {}

    /**
     * @return list<array{member_id: int, member_name: string, member_number: string, channel: string, read_at: string|null, coupon_status: string, coupon_used_at: string|null}>
     */
    public function getDeliveryRecipients(Announcement $announcement): array
    {
        $targetMemberIds = $this->resolveAnnouncementTargetMemberIds($announcement);
        if ($targetMemberIds === []) {
            return [];
        }

        $members = Member::withTrashed()
            ->where('project_id', (int) $announcement->project_id)
            ->whereIn('id', $targetMemberIds)
            ->with([
                'user' => fn ($query) => $query->withTrashed(),
                'user.devices',
            ])
            ->get()
            ->keyBy(fn (Member $member) => (int) $member->id);

        $userIds = $members->pluck('user_id')->filter()->map(fn ($id) => (int) $id)->values()->all();
        $reads = AnnouncementRead::query()
            ->where('announcement_id', (int) $announcement->id)
            ->whereIn('user_id', $userIds)
            ->get()
            ->keyBy(fn (AnnouncementRead $read) => (int) $read->user_id);

        $memberCoupons = collect();
        $deliveryCouponIds = $announcement->deliveryCouponIds();
        if ($deliveryCouponIds !== []) {
            $memberCoupons = MemberCoupon::query()
                ->where('project_id', (int) $announcement->project_id)
                ->whereIn('coupon_id', $deliveryCouponIds)
                ->where('issue_source_type', MemberCouponSource::ANNOUNCEMENT)
                ->where('issue_source_id', (string) $announcement->id)
                ->whereIn('member_id', $targetMemberIds)
                ->orderByDesc('id')
                ->get()
                ->sortByDesc(fn (MemberCoupon $coupon): bool => $coupon->status === 'used')
                ->unique('member_id')
                ->keyBy(fn (MemberCoupon $memberCoupon) => (int) $memberCoupon->member_id);
        }

        $hasCoupon = $announcement->hasDeliveryCoupons();

        return collect($targetMemberIds)
            ->map(function (int $memberId) use ($members, $reads, $memberCoupons, $hasCoupon): ?array {
                /** @var Member|null $member */
                $member = $members->get($memberId);
                if ($member === null) {
                    return null;
                }
                /** @var AnnouncementRead|null $read */
                $read = $member->user_id !== null ? $reads->get((int) $member->user_id) : null;
                /** @var MemberCoupon|null $memberCoupon */
                $memberCoupon = $memberCoupons->get($memberId);
                $couponStatus = 'none';
                if ($hasCoupon) {
                    $couponStatus = $memberCoupon === null ? 'not_issued' : (string) $memberCoupon->status;
                }

                return [
                    'member_id' => (int) $member->id,
                    'member_name' => trim((string) ($member->user?->name ?? '')) !== '' ? (string) $member->user->name : '名前未登録',
                    'member_number' => trim((string) $member->member_number) !== '' ? (string) $member->member_number : '—',
                    'channel' => MemberChannel::label($member->user),
                    'read_at' => $read?->read_at?->format('Y/m/d H:i'),
                    'coupon_status' => $couponStatus,
                    'coupon_used_at' => $memberCoupon?->used_at?->format('Y/m/d H:i'),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function finalizeAudienceForSend(Announcement $announcement): void
    {
        if (! $announcement->shouldResolveAudienceAtSend()) {
            return;
        }

        $announcement->target_member_ids = $this->liveMemberIds($announcement)->all();
        $announcement->save();
    }

    public function issueDeliveryCouponIfSet(Announcement $announcement): void
    {
        $couponIds = $announcement->deliveryCouponIds();
        if ($couponIds === []) {
            return;
        }

        $targetMemberIds = $this->resolveAnnouncementTargetMemberIds($announcement);
        $hasTargetSnapshot = is_array($announcement->target_member_ids);
        $isTargeted = in_array($announcement->delivery_type, [
            Announcement::DELIVERY_TYPE_PERSONAL,
            Announcement::DELIVERY_TYPE_SEGMENT,
            Announcement::DELIVERY_TYPE_AFTER_REGISTRATION,
        ], true);
        if (($hasTargetSnapshot || $isTargeted) && $targetMemberIds === []) {
            return;
        }

        foreach ($couponIds as $couponId) {
            $this->memberBenefitService->issueCouponToMembers(
                (int) $announcement->project_id,
                $couponId,
                $targetMemberIds,
                MemberCouponSource::ANNOUNCEMENT,
                (string) $announcement->id,
                false,
            );
        }
    }

    /** @return Collection<int, int> */
    public function liveMemberIds(Announcement $announcement): Collection
    {
        return $this->storeAudience->filter($announcement, $this->resolveLiveMemberIds($announcement));
    }

    private function resolveLiveMemberIds(Announcement $announcement): Collection
    {
        $projectId = (int) $announcement->project_id;
        if ($announcement->delivery_type === Announcement::DELIVERY_TYPE_PERSONAL) {
            return $this->memberAdminService->filterValidMemberIds(
                $projectId,
                $this->normalizeMemberIds($announcement->target_member_ids),
            );
        }
        if ($announcement->delivery_type === Announcement::DELIVERY_TYPE_SEGMENT) {
            $criteria = is_array($announcement->segment_criteria) ? $announcement->segment_criteria : null;
            if ($criteria === null) {
                return collect();
            }

            return $this->memberSegmentService->getMemberIds($projectId, $criteria);
        }

        return $this->memberAdminService->getIdsByProject($projectId);
    }

    /** @return list<int> */
    private function resolveAnnouncementTargetMemberIds(Announcement $announcement): array
    {
        if ($announcement->delivery_status === Announcement::DELIVERY_STATUS_SCHEDULED
            && $announcement->shouldResolveAudienceAtSend()) {
            return $this->liveMemberIds($announcement)->all();
        }

        if (is_array($announcement->target_member_ids)) {
            return $this->normalizeMemberIds($announcement->target_member_ids);
        }

        if (in_array($announcement->delivery_type, [
            Announcement::DELIVERY_TYPE_PERSONAL,
            Announcement::DELIVERY_TYPE_SEGMENT,
            Announcement::DELIVERY_TYPE_AFTER_REGISTRATION,
        ], true)) {
            return [];
        }

        return $this->memberAdminService->getIdsByProject((int) $announcement->project_id)->all();
    }

    /**
     * @return list<int>
     */
    private function normalizeMemberIds(mixed $rawIds): array
    {
        if (! is_array($rawIds)) {
            return [];
        }

        return collect($rawIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
