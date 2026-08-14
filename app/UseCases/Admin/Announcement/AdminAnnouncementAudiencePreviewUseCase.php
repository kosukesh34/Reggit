<?php

namespace App\UseCases\Admin\Announcement;

use App\Enums\MemberNotificationPreference;
use App\Models\Announcement;
use App\Models\Member;
use App\Services\Admin\MemberAdminService;
use App\Services\Admin\MemberSegmentService;
use App\Support\MemberChannel;
use Illuminate\Support\Collection;

class AdminAnnouncementAudiencePreviewUseCase
{
    private const PER_PAGE = 20;

    public function __construct(
        private MemberAdminService $memberAdminService,
        private MemberSegmentService $memberSegmentService,
    ) {}

    /**
     * @param  array<string, string|string[]>  $query
     * @return array<string, mixed>
     */
    public function handle(int $projectId, array $query): array
    {
        $audienceType = (string) ($query['audience_type'] ?? Announcement::DELIVERY_TYPE_IMMEDIATE_BULK);
        $hasCoupon = (int) ($query['delivery_coupon_id'] ?? 0) > 0;
        $memberIds = $this->resolveMemberIds($projectId, $audienceType, $query);
        $count = $memberIds->count();
        $lastPage = max(1, (int) ceil($count / self::PER_PAGE));
        $page = min(max(1, (int) ($query['page'] ?? 1)), $lastPage);
        $pageIds = $memberIds->slice(($page - 1) * self::PER_PAGE, self::PER_PAGE)->values();

        $members = Member::query()
            ->where('project_id', $projectId)
            ->whereIn('id', $pageIds->all())
            ->with(['user.devices', 'tags'])
            ->get()
            ->keyBy(fn (Member $member) => (int) $member->id);

        $rows = $pageIds->map(function (int $memberId) use ($members, $hasCoupon): ?array {
            /** @var Member|null $member */
            $member = $members->get($memberId);
            if ($member === null) {
                return null;
            }

            $preference = MemberNotificationPreference::normalize($member->notification_preference);
            $preferenceAllows = MemberNotificationPreference::allowsAnnouncement($preference, $hasCoupon);
            $hasNotificationChannel = MemberChannel::channels($member->user) !== [];
            $notificationReason = match (true) {
                ! $preferenceAllows && $preference === MemberNotificationPreference::COUPON_ONLY => 'クーポンが付かない配信は通知しません',
                ! $preferenceAllows => '会員が通知を受け取らない設定です',
                ! $hasNotificationChannel => 'LINE・Pushの送信先がありません',
                default => '送信できます',
            };

            return [
                'id' => (int) $member->id,
                'member_number' => trim((string) $member->member_number) !== '' ? (string) $member->member_number : '—',
                'name' => trim((string) ($member->user?->name ?? '')) !== '' ? (string) $member->user->name : '名前未登録',
                'rank' => trim((string) $member->rank) !== '' ? (string) $member->rank : '—',
                'channel' => MemberChannel::label($member->user),
                'kana' => trim((string) ($member->user?->kana ?? '')),
                'email' => trim((string) ($member->user?->email ?? '')),
                'phone' => trim((string) ($member->user?->phone ?? '')),
                'birthday' => $member->birthday?->format('Y/m/d') ?? '—',
                'sex' => match ((string) ($member->sex ?? '')) {
                    '1' => '男性',
                    '2' => '女性',
                    default => '—',
                },
                'points' => (int) $member->points,
                'last_visited_at' => $member->last_visited_at?->format('Y/m/d H:i') ?? '—',
                'tags' => $member->tags->map(fn ($tag): array => [
                    'id' => (int) $tag->id,
                    'name' => (string) $tag->name,
                    'color' => (string) $tag->color,
                ])->values()->all(),
                'notification_preference' => $preference,
                'notification_preference_label' => MemberNotificationPreference::getLabel($preference),
                'notification_sendable' => $preferenceAllows && $hasNotificationChannel,
                'notification_reason' => $notificationReason,
            ];
        })->filter()->values()->all();

        return [
            'count' => $count,
            'members' => $rows,
            'notification_channels' => $this->notificationChannelCounts($projectId, $memberIds, $hasCoupon),
            'page' => $page,
            'last_page' => $lastPage,
            'per_page' => self::PER_PAGE,
        ];
    }

    /**
     * @param  array<string, string|string[]>  $query
     * @return Collection<int, int>
     */
    private function resolveMemberIds(int $projectId, string $audienceType, array $query): Collection
    {
        if ($audienceType === Announcement::DELIVERY_TYPE_PERSONAL) {
            $rawIds = collect($query['target_member_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            return $this->memberAdminService->filterValidMemberIds($projectId, $rawIds);
        }

        if ($audienceType === Announcement::DELIVERY_TYPE_SEGMENT) {
            $criteria = $this->memberSegmentService->buildCriteriaFromInput($query);

            return $criteria === null
                ? collect()
                : $this->memberSegmentService->getMemberIds($projectId, $criteria)->values();
        }

        return $this->memberAdminService->getIdsByProject($projectId);
    }

    /**
     * @param  Collection<int, int>  $memberIds
     * @return array{line: int, push: int, unreachable: int, opted_out: int}
     */
    private function notificationChannelCounts(int $projectId, Collection $memberIds, bool $hasCoupon): array
    {
        if ($memberIds->isEmpty()) {
            return ['line' => 0, 'push' => 0, 'unreachable' => 0, 'opted_out' => 0];
        }

        $baseQuery = Member::query()
            ->where('project_id', $projectId)
            ->whereIn('id', $memberIds->all());
        $lineConstraint = static function ($query): void {
            $query->whereNotNull('line_user_id')
                ->where('line_user_id', '!=', '')
                ->whereRaw('line_user_id REGEXP ?', ['^U[0-9a-fA-F]{32}$']);
        };
        $pushConstraint = static function ($query): void {
            $query->whereNotNull('push_token')->where('push_token', '!=', '');
        };
        $eligibleQuery = (clone $baseQuery)->where(function ($query) use ($hasCoupon): void {
            $query->whereNull('notification_preference')
                ->orWhereIn('notification_preference', MemberNotificationPreference::eligibleValues($hasCoupon));
        });
        $eligibleCount = (clone $eligibleQuery)->count();

        return [
            'line' => (clone $eligibleQuery)->whereHas('user', $lineConstraint)->count(),
            'push' => (clone $eligibleQuery)->whereHas('user.devices', $pushConstraint)->count(),
            'unreachable' => (clone $eligibleQuery)
                ->whereDoesntHave('user', $lineConstraint)
                ->whereDoesntHave('user.devices', $pushConstraint)
                ->count(),
            'opted_out' => max(0, $memberIds->count() - $eligibleCount),
        ];
    }
}
