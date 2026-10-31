<?php

namespace App\UseCases\Admin\Coupon;

use App\Enums\CouponDeliveryStatus;
use App\Enums\CouponDeliveryType;
use App\Models\Coupon;
use App\Models\CouponDelivery;
use App\Services\Admin\CouponDeliveryTargetService;
use App\Services\Admin\MemberBenefitService;
use App\Util\DateUtil;
use Illuminate\Support\Facades\DB;

class AdminCouponBulkIssueUseCase
{
    public function __construct(
        private MemberBenefitService $memberBenefitService,
        private CouponDeliveryTargetService $targetService,
    ) {}

    /**
     * @param  array<string, string|string[]|int|bool|null>  $validated
     * @return array{issued: int, scheduled: bool, message: string}
     */
    /** 配信できる（有効な）クーポンか */
    public function isDeliverable(int $projectId, int $couponId): bool
    {
        return Coupon::where('project_id', $projectId)->whereKey($couponId)->where('is_active', true)->exists();
    }

    public function handle(int $projectId, int $couponId, array $validated): array
    {
        $coupon = Coupon::query()->where('project_id', $projectId)->whereKey($couponId)->first();
        if ($coupon === null) {
            throw new \InvalidArgumentException('クーポンが見つかりません');
        }
        if ($coupon->end_at !== null && $coupon->end_at->lt(now())) {
            throw new \InvalidArgumentException('このクーポンは期限切れです');
        }

        $deliveryType = (string) ($validated['delivery_type'] ?? CouponDeliveryType::IMMEDIATE_ALL);
        $resolved = $this->targetService->resolve($projectId, $deliveryType, $validated);
        $sendNotification = $this->shouldSendNotification($validated);

        if ($resolved['error'] !== null) {
            throw new \InvalidArgumentException($resolved['error']);
        }

        $memberIds = $resolved['member_ids'];

        if (CouponDeliveryType::isEventRankUpArrival($deliveryType)) {
            $rank = trim((string) ($resolved['segment_criteria']['rank'] ?? ''));
            if ($rank === '') {
                throw new \InvalidArgumentException('到達ランクを選択してください');
            }

            CouponDelivery::create([
                'project_id' => $projectId,
                'coupon_id' => $couponId,
                'delivery_type' => $deliveryType,
                'status' => CouponDeliveryStatus::ACTIVE,
                'scheduled_at' => null,
                'target_member_ids' => null,
                'issued_member_ids' => null,
                'segment_criteria' => $resolved['segment_criteria'],
                'issued_count' => 0,
                'send_notification' => $sendNotification,
            ]);

            return [
                'issued' => 0,
                'scheduled' => true,
                'message' => "ランクアップ達成配信を登録しました（到達ランク: {$rank}）",
            ];
        }

        $isScheduled = CouponDeliveryType::isScheduled($deliveryType);

        if ($isScheduled) {
            $scheduledAt = DateUtil::nullableCarbon((string) $validated['scheduled_at']);
            if ($scheduledAt === null) {
                throw new \InvalidArgumentException('配信日時が不正です');
            }
            CouponDelivery::create([
                'project_id' => $projectId,
                'coupon_id' => $couponId,
                'delivery_type' => $deliveryType,
                'status' => CouponDeliveryStatus::SCHEDULED,
                'scheduled_at' => $scheduledAt,
                'target_member_ids' => $this->normalizeMemberIds($memberIds),
                'segment_criteria' => $resolved['segment_criteria'],
                'send_notification' => $sendNotification,
            ]);

            $count = $this->targetService->countTargets($projectId, $deliveryType, $validated);
            $formatted = $scheduledAt->format('Y/m/d H:i');

            return [
                'issued' => 0,
                'scheduled' => true,
                'message' => "クーポン配布を予約しました（{$formatted}・対象 {$count}人）",
            ];
        }

        if ($memberIds === [] && ! CouponDeliveryType::isAllMembersDelivery($deliveryType)) {
            throw new \InvalidArgumentException('配布対象の会員がいません');
        }

        return DB::transaction(function () use ($projectId, $couponId, $memberIds, $sendNotification, $deliveryType, $resolved): array {
            $result = $this->memberBenefitService->issueCouponToMembers(
                $projectId,
                $couponId,
                $memberIds,
                null,
                null,
                $sendNotification,
            );

            CouponDelivery::create([
                'project_id' => $projectId,
                'coupon_id' => $couponId,
                'delivery_type' => $deliveryType,
                'status' => CouponDeliveryStatus::SENT,
                'sent_at' => now(),
                'target_member_ids' => $this->normalizeMemberIds($memberIds),
                'issued_member_ids' => $this->normalizeMemberIds($result['member_ids']),
                'segment_criteria' => $resolved['segment_criteria'],
                'issued_count' => $result['count'],
                'send_notification' => $sendNotification,
            ]);

            return [
                'issued' => $result['count'],
                'scheduled' => false,
                'message' => "{$result['count']}件の会員にクーポンを配布しました",
            ];
        });
    }

    /**
     * @param  list<int>  $memberIds
     * @return list<int>|null
     */
    private function normalizeMemberIds(array $memberIds): ?array
    {
        $normalized = array_values(array_filter(
            array_map(static fn ($id) => (int) $id, $memberIds),
            static fn (int $id) => $id > 0,
        ));

        return $normalized === [] ? null : $normalized;
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
