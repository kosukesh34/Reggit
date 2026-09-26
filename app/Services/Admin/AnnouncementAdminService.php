<?php

namespace App\Services\Admin;

use App\Enums\MemberCouponSource;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Support\MemberChannel;
use App\Util\DateUtil;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AnnouncementAdminService
{
    public function __construct(
        private MemberSegmentService $memberSegmentService,
        private MemberAdminService $memberAdminService,
        private MemberBenefitService $memberBenefitService,
    ) {}

    /** @return list<string> */
    public function deliveryTypeFilterKeys(): array
    {
        return [
            'all',
            Announcement::DELIVERY_TYPE_IMMEDIATE_BULK,
            Announcement::DELIVERY_TYPE_PERSONAL,
            Announcement::DELIVERY_TYPE_SEGMENT,
            Announcement::DELIVERY_TYPE_AFTER_REGISTRATION,
        ];
    }

    /**
     * @param  array<string, string|null>  $input
     * @return array{
     *     delivery_type: string,
     *     keyword: string,
     *     published_from: string,
     *     published_to: string,
     * }
     */
    public function normalizeIndexFilters(array $input): array
    {
        return [
            'delivery_type' => $this->normalizeDeliveryTypeFilter($input['delivery_type'] ?? null),
            'keyword' => trim((string) ($input['keyword'] ?? '')),
            'published_from' => $this->normalizeDate($input['published_from'] ?? null) ?? '',
            'published_to' => $this->normalizeDate($input['published_to'] ?? null) ?? '',
        ];
    }

    public function normalizeDeliveryTypeFilter(?string $deliveryType): string
    {
        $normalized = trim((string) $deliveryType);
        if ($normalized === '' || ! in_array($normalized, $this->deliveryTypeFilterKeys(), true)) {
            return 'all';
        }

        return $normalized;
    }

    /**
     * @param  array{
     *     delivery_type: string,
     *     keyword: string,
     *     published_from: string,
     *     published_to: string,
     * }  $filters
     */
    public function getPaginatedList(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->buildFilteredQuery($filters)
            ->withCount('reads')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * 配信形態タブの件数。期間・キーワード条件は反映し、delivery_type は除外する。
     *
     * @param  array{
     *     delivery_type: string,
     *     keyword: string,
     *     published_from: string,
     *     published_to: string,
     * }  $filters
     * @return array<string, int>
     */
    public function getDeliveryTypeCounts(array $filters): array
    {
        $baseFilters = $filters;
        $baseFilters['delivery_type'] = 'all';
        $counts = ['all' => $this->buildFilteredQuery($baseFilters)->count()];
        foreach ($this->deliveryTypeFilterKeys() as $key) {
            if ($key === 'all') {
                continue;
            }
            $typeFilters = $baseFilters;
            $typeFilters['delivery_type'] = $key;
            $counts[$key] = $this->buildFilteredQuery($typeFilters)->count();
        }

        return $counts;
    }

    /**
     * @param  array{
     *     delivery_type: string,
     *     keyword: string,
     *     published_from: string,
     *     published_to: string,
     * }  $filters
     * @return Builder<Announcement>
     */
    private function buildFilteredQuery(array $filters): Builder
    {
        $query = Announcement::query();

        if ($filters['delivery_type'] !== 'all') {
            $query->where('delivery_type', $filters['delivery_type']);
        }

        if ($filters['keyword'] !== '') {
            $keyword = $filters['keyword'];
            $query->where('title', 'like', '%'.addcslashes($keyword, '%_\\').'%');
        }

        if ($filters['published_from'] !== '') {
            $from = DateUtil::nullableCarbon($filters['published_from']);
            if ($from !== null) {
                $query->where('published_at', '>=', $from->startOfDay());
            }
        }

        if ($filters['published_to'] !== '') {
            $to = DateUtil::nullableCarbon($filters['published_to']);
            if ($to !== null) {
                $query->where('published_at', '<=', $to->endOfDay());
            }
        }

        return $query;
    }

    private function normalizeDate(?string $date): ?string
    {
        $normalized = trim((string) $date);
        if ($normalized === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalized) !== 1) {
            return null;
        }

        return DateUtil::nullableCarbon($normalized)?->toDateString();
    }

    public function create(int $projectId, array $data): Announcement
    {
        $data['project_id'] = $projectId;

        return Announcement::create($data);
    }

    public function findOrFail(int $id): Announcement
    {
        return Announcement::findOrFail($id);
    }

    /**
     * @return list<array{member_id: int|null, member_name: string, member_number: string, read_at: string}>
     */
    public function getReadsForAnnouncement(int $announcementId): array
    {
        $reads = AnnouncementRead::query()
            ->where('announcement_id', $announcementId)
            ->with(['user.member'])
            ->orderByDesc('read_at')
            ->orderByDesc('id')
            ->get();

        $rows = [];
        foreach ($reads as $read) {
            $user = $read->user;
            $member = $user?->member;
            $rows[] = [
                'member_id' => $member !== null ? (int) $member->id : null,
                'member_name' => $user !== null && trim((string) $user->name) !== ''
                    ? (string) $user->name
                    : '—',
                'member_number' => $member !== null && trim((string) $member->member_number) !== ''
                    ? (string) $member->member_number
                    : '—',
                'read_at' => $read->read_at !== null
                    ? $read->read_at->format('Y/m/d H:i')
                    : '—',
            ];
        }

        return $rows;
    }

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
        if ($announcement->delivery_coupon_id !== null) {
            $memberCoupons = MemberCoupon::query()
                ->where('project_id', (int) $announcement->project_id)
                ->where('coupon_id', (int) $announcement->delivery_coupon_id)
                ->where('issue_source_type', MemberCouponSource::ANNOUNCEMENT)
                ->where('issue_source_id', (string) $announcement->id)
                ->whereIn('member_id', $targetMemberIds)
                ->orderByDesc('id')
                ->get()
                ->unique('member_id')
                ->keyBy(fn (MemberCoupon $memberCoupon) => (int) $memberCoupon->member_id);
        }

        $hasCoupon = $announcement->delivery_coupon_id !== null;

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
        if ($announcement->delivery_coupon_id === null) {
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

        $this->memberBenefitService->issueCouponToMembers(
            (int) $announcement->project_id,
            (int) $announcement->delivery_coupon_id,
            $targetMemberIds,
            MemberCouponSource::ANNOUNCEMENT,
            (string) $announcement->id,
            false,
        );
    }

    /** @return Collection<int, int> */
    public function liveMemberIds(Announcement $announcement): Collection
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

    public function update(int $id, array $data): Announcement
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->update($data);

        return $announcement;
    }

    public function delete(int $id): void
    {
        Announcement::whereKey($id)->delete();
    }
}
