<?php

namespace App\UseCases\Admin\Announcement;

use App\Enums\AnnouncementAudienceResolveMode;
use App\Jobs\SendScheduledAnnouncementJob;
use App\Models\Announcement;
use App\Services\Admin\AnnouncementAdminService;
use App\Services\Admin\CouponAdminService;
use App\Services\Admin\MemberAdminService;
use App\Services\Admin\MemberSegmentService;
use App\Services\AnnouncementNotificationService;
use App\UseCases\Admin\Announcement\Data\StoreDeliveryOutput;
use App\Util\DateUtil;
use Illuminate\Support\Collection;

class AdminAnnouncementStoreDeliveryUseCase
{
    public function __construct(
        private AnnouncementNotificationService $announcementNotificationService,
        private MemberSegmentService $memberSegmentService,
        private MemberAdminService $memberAdminService,
        private AnnouncementAdminService $announcementAdminService,
        private CouponAdminService $couponAdminService
    ) {}

    /**
     * @param array{
     *     audience_type: string,
     *     delivery_timing: string,
     *     title: string,
     *     body?: string,
     *     is_pinned?: string,
     *     expires_at?: string,
     *     image_url?: string,
     *     scheduled_at?: string,
     *     target_member_ids?: string[],
     *     segment_period_days?: string,
     *     segment_condition?: string,
     *     segment_min_amount?: string,
     *     delivery_coupon_id?: string,
     *     audience_resolve_mode?: string,
     *     send_notification?: string|int|bool,
     * } $validated
     */
    public function handle(int $projectId, array $validated): StoreDeliveryOutput
    {
        $audienceType = (string) $validated['audience_type'];
        $deliveryTiming = (string) $validated['delivery_timing'];
        $locksAudience = $this->locksAudience($audienceType, $deliveryTiming, $validated);
        $targetMemberIds = $this->resolveTargetMemberIds($projectId, $audienceType, $validated, $locksAudience);

        if ($targetMemberIds === null) {
            return new StoreDeliveryOutput(false, true, '指定された会員が見つかりません', 'target_member_ids');
        }

        $announcement = $this->createAnnouncement(
            $projectId,
            $audienceType,
            $deliveryTiming,
            $validated,
            $targetMemberIds,
            $locksAudience,
        );

        return $this->dispatchOrSend($announcement, $deliveryTiming);
    }

    /**
     * @param  array<string, string|string[]>  $validated
     * @return Collection<int, int>|null null = バリデーションエラー
     */
    private function resolveTargetMemberIds(
        int $projectId,
        string $deliveryType,
        array $validated,
        bool $locksAudience,
    ): ?Collection {
        if ($deliveryType === Announcement::DELIVERY_TYPE_PERSONAL) {
            return $this->resolvePersonalTargets($projectId, $validated);
        }
        if (! $locksAudience) {
            return collect();
        }

        if ($deliveryType === Announcement::DELIVERY_TYPE_SEGMENT) {
            $criteria = $this->memberSegmentService->buildCriteriaFromInput($validated);
            if ($criteria === null) {
                return collect();
            }

            return $this->memberSegmentService->getMemberIds($projectId, $criteria);
        }

        return $this->memberAdminService->getIdsByProject($projectId);
    }

    /**
     * @param  array<string, string|string[]>  $validated
     */
    private function locksAudience(string $audienceType, string $deliveryTiming, array $validated): bool
    {
        if ($deliveryTiming !== Announcement::DELIVERY_TIMING_SCHEDULED) {
            return true;
        }
        if ($audienceType === Announcement::DELIVERY_TYPE_PERSONAL) {
            return true;
        }

        return (string) ($validated['audience_resolve_mode'] ?? AnnouncementAudienceResolveMode::SNAPSHOT)
            !== AnnouncementAudienceResolveMode::AT_SEND;
    }

    /**
     * @param  array<string, string|string[]>  $validated
     * @return Collection<int, int>|null
     */
    private function resolvePersonalTargets(int $projectId, array $validated): ?Collection
    {
        $rawIds = collect($validated['target_member_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $validIds = $this->memberAdminService->filterValidMemberIds($projectId, $rawIds);

        return $validIds->count() === count($rawIds) ? $validIds : null;
    }

    /**
     * @param  array<string, string|string[]>  $validated
     * @param  Collection<int, int>  $targetMemberIds
     */
    private function createAnnouncement(
        int $projectId,
        string $audienceType,
        string $deliveryTiming,
        array $validated,
        Collection $targetMemberIds,
        bool $locksAudience,
    ): Announcement {
        $isScheduled = $deliveryTiming === Announcement::DELIVERY_TIMING_SCHEDULED;
        $scheduledAt = $isScheduled ? DateUtil::nullableCarbon((string) $validated['scheduled_at']) : null;
        $now = now();

        return $this->announcementAdminService->create($projectId, [
            'title' => (string) $validated['title'],
            'body' => $validated['body'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
            'is_pinned' => (bool) ($validated['is_pinned'] ?? false),
            'send_notification' => $this->shouldSendNotification($validated),
            'expires_at' => $validated['expires_at'] ?? null,
            'delivery_type' => $audienceType,
            'delivery_timing' => $deliveryTiming,
            'scheduled_at' => $scheduledAt,
            'delivery_status' => $isScheduled
                ? Announcement::DELIVERY_STATUS_SCHEDULED
                : Announcement::DELIVERY_STATUS_SENT,
            'published_at' => $isScheduled ? null : $now,
            'sent_at' => $isScheduled ? null : $now,
            'target_member_ids' => $locksAudience ? $targetMemberIds->all() : null,
            'audience_resolve_mode' => $locksAudience
                ? AnnouncementAudienceResolveMode::SNAPSHOT
                : AnnouncementAudienceResolveMode::AT_SEND,
            'segment_criteria' => $audienceType === Announcement::DELIVERY_TYPE_SEGMENT
                ? $this->memberSegmentService->buildCriteriaFromInput($validated)
                : null,
            'delivery_coupon_id' => $this->resolveDeliveryCouponId($projectId, $validated),
        ]);
    }

    private function dispatchOrSend(Announcement $announcement, string $deliveryTiming): StoreDeliveryOutput
    {
        $isScheduled = $deliveryTiming === Announcement::DELIVERY_TIMING_SCHEDULED;

        if ($isScheduled && $announcement->scheduled_at !== null) {
            SendScheduledAnnouncementJob::dispatch($announcement->id)
                ->delay($announcement->scheduled_at);

            $formattedDate = DateUtil::nullableCarbon($announcement->scheduled_at)?->format('Y/m/d H:i') ?? '';

            return new StoreDeliveryOutput(true, false, "お知らせ配信を予約しました（{$formattedDate}）");
        }

        if ($announcement->send_notification === false) {
            $this->announcementAdminService->issueDeliveryCouponIfSet($announcement);

            return new StoreDeliveryOutput(false, false, 'お知らせを掲載しました（通知は送信していません）');
        }

        $result = $this->announcementNotificationService->send($announcement);
        $this->announcementAdminService->issueDeliveryCouponIfSet($announcement);

        $sent = $result['sent'];
        $failed = $result['failed'];
        $skipped = $result['skipped'];
        $lineSent = $result['line_sent'];
        $lineFailed = $result['line_failed'];
        $lineSkipped = $result['line_skipped'];

        return new StoreDeliveryOutput(
            false,
            false,
            "お知らせを配信しました（アプリ: 送信 {$sent}件 / 失敗 {$failed}件 / スキップ {$skipped}件、LINE: 送信 {$lineSent}件 / 失敗 {$lineFailed}件 / スキップ {$lineSkipped}件）"
        );
    }

    /** @param array<string, string|string[]> $validated */
    private function resolveDeliveryCouponId(int $projectId, array $validated): ?int
    {
        if (! isset($validated['delivery_coupon_id'])) {
            return null;
        }

        $couponId = (int) $validated['delivery_coupon_id'];
        if ($couponId <= 0) {
            return null;
        }

        return $this->couponAdminService->existsForProject($projectId, $couponId) ? $couponId : null;
    }

    /**
     * @param  array<string, string|string[]|int|bool|null>  $validated
     */
    private function shouldSendNotification(array $validated): bool
    {
        if (! array_key_exists('send_notification', $validated)) {
            return true;
        }
        $value = $validated['send_notification'];

        return $value === true || $value === 1 || $value === '1';
    }
}
