<?php

namespace App\UseCases\Admin\Announcement;

use App\Jobs\SendScheduledAnnouncementJob;
use App\Models\Announcement;
use App\Services\Admin\AnnouncementAdminService;
use App\Services\Admin\CouponAdminService;
use App\Services\Admin\MemberAdminService;
use App\Services\Admin\MemberSegmentService;
use App\Services\PushNotificationService;
use App\UseCases\Admin\Announcement\Data\StoreDeliveryOutput;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AdminAnnouncementStoreDeliveryUseCase
{
    public function __construct(
        private PushNotificationService $pushNotificationService,
        private MemberSegmentService $memberSegmentService,
        private MemberAdminService $memberAdminService,
        private AnnouncementAdminService $announcementAdminService,
        private CouponAdminService $couponAdminService
    ) {}

    /**
     * @param array{
     *     delivery_type: string,
     *     title: string,
     *     body?: string,
     *     is_pinned?: string,
     *     expires_at?: string,
     *     scheduled_at?: string,
     *     target_member_ids?: string[],
     *     segment_period_days?: string,
     *     segment_condition?: string,
     *     segment_min_amount?: string,
     *     delivery_coupon_id?: string,
     * } $validated
     */
    public function handle(int $projectId, array $validated): StoreDeliveryOutput
    {
        $deliveryType = (string) $validated['delivery_type'];
        $targetMemberIds = $this->resolveTargetMemberIds($projectId, $deliveryType, $validated);

        if ($targetMemberIds === null) {
            return new StoreDeliveryOutput(false, true, '指定された会員が見つかりません', 'target_member_ids');
        }

        $announcement = $this->createAnnouncement($projectId, $deliveryType, $validated, $targetMemberIds);

        return $this->dispatchOrSend($announcement, $deliveryType);
    }

    /**
     * @param  array<string, string|string[]>  $validated
     * @return Collection<int, int>|null null = バリデーションエラー
     */
    private function resolveTargetMemberIds(int $projectId, string $deliveryType, array $validated): ?Collection
    {
        if ($deliveryType === Announcement::DELIVERY_TYPE_PERSONAL) {
            return $this->resolvePersonalTargets($projectId, $validated);
        }

        if ($deliveryType === Announcement::DELIVERY_TYPE_SEGMENT) {
            $criteria = $this->memberSegmentService->buildCriteriaFromInput($validated);
            if ($criteria === null) {
                return collect();
            }

            return $this->memberSegmentService->getMemberIds($projectId, $criteria);
        }

        return collect();
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
        string $deliveryType,
        array $validated,
        Collection $targetMemberIds
    ): Announcement {
        $isScheduled = $deliveryType === Announcement::DELIVERY_TYPE_SCHEDULED;
        $scheduledAt = $isScheduled ? Carbon::parse((string) $validated['scheduled_at']) : null;
        $now = now();

        return $this->announcementAdminService->create($projectId, [
            'title' => (string) $validated['title'],
            'body' => $validated['body'] ?? null,
            'is_pinned' => (bool) ($validated['is_pinned'] ?? false),
            'expires_at' => $validated['expires_at'] ?? null,
            'delivery_type' => $deliveryType,
            'scheduled_at' => $scheduledAt,
            'delivery_status' => $isScheduled
                ? Announcement::DELIVERY_STATUS_SCHEDULED
                : Announcement::DELIVERY_STATUS_SENT,
            'published_at' => $isScheduled ? null : $now,
            'sent_at' => $isScheduled ? null : $now,
            'target_member_ids' => $targetMemberIds->isEmpty() ? null : $targetMemberIds->all(),
            'segment_criteria' => $deliveryType === Announcement::DELIVERY_TYPE_SEGMENT
                ? $this->memberSegmentService->buildCriteriaFromInput($validated)
                : null,
            'delivery_coupon_id' => $this->resolveDeliveryCouponId($projectId, $validated),
        ]);
    }

    private function dispatchOrSend(Announcement $announcement, string $deliveryType): StoreDeliveryOutput
    {
        $isScheduled = $deliveryType === Announcement::DELIVERY_TYPE_SCHEDULED;

        if ($isScheduled && $announcement->scheduled_at !== null) {
            SendScheduledAnnouncementJob::dispatch($announcement->id)
                ->delay($announcement->scheduled_at);

            $formattedDate = Carbon::parse($announcement->scheduled_at)->format('Y/m/d H:i');

            return new StoreDeliveryOutput(true, false, "お知らせ配信を予約しました（{$formattedDate}）");
        }

        $result = $this->pushNotificationService->sendAnnouncement($announcement);
        $this->issueCouponIfSet($announcement);

        $sent = $result['sent'];
        $failed = $result['failed'];
        $skipped = $result['skipped'];

        return new StoreDeliveryOutput(
            false,
            false,
            "お知らせを配信しました（送信: {$sent}件 / 失敗: {$failed}件 / スキップ: {$skipped}件）"
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

    private function issueCouponIfSet(Announcement $announcement): void
    {
        if ($announcement->delivery_coupon_id === null) {
            return;
        }

        $targetMemberIds = is_array($announcement->target_member_ids) && $announcement->target_member_ids !== []
            ? $announcement->target_member_ids
            : [];

        $this->memberAdminService->issueCouponToMembers(
            (int) $announcement->project_id,
            (int) $announcement->delivery_coupon_id,
            $targetMemberIds
        );
    }
}
