<?php

namespace App\UseCases\Admin\Coupon;

use App\Enums\CouponDeliveryStatus;
use App\Enums\CouponDeliveryType;
use App\Jobs\SendScheduledCouponDeliveryJob;
use App\Models\Coupon;
use App\Models\CouponDelivery;
use App\Services\Admin\CouponDeliveryTargetService;
use App\Services\Admin\MemberAdminService;
use Illuminate\Support\Carbon;

class AdminCouponBulkIssueUseCase
{
    public function __construct(
        private MemberAdminService $memberAdminService,
        private CouponDeliveryTargetService $targetService,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     * @return array{issued: int, scheduled: bool, message: string}
     */
    public function handle(int $projectId, int $couponId, array $validated): array
    {
        if (Coupon::query()->where('project_id', $projectId)->whereKey($couponId)->doesntExist()) {
            throw new \InvalidArgumentException('クーポンが見つかりません');
        }

        $deliveryType = (string) ($validated['delivery_type'] ?? CouponDeliveryType::IMMEDIATE_ALL);
        $resolved = $this->targetService->resolve($projectId, $deliveryType, $validated);

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
            ]);

            return [
                'issued' => 0,
                'scheduled' => true,
                'message' => "ランクアップ達成配信を登録しました（到達ランク: {$rank}）",
            ];
        }

        $isScheduled = CouponDeliveryType::isScheduled($deliveryType);

        if ($isScheduled) {
            $scheduledAt = Carbon::parse((string) $validated['scheduled_at']);
            $delivery = CouponDelivery::create([
                'project_id' => $projectId,
                'coupon_id' => $couponId,
                'delivery_type' => $deliveryType,
                'status' => CouponDeliveryStatus::SCHEDULED,
                'scheduled_at' => $scheduledAt,
                'target_member_ids' => $this->normalizeMemberIds($memberIds),
                'segment_criteria' => $resolved['segment_criteria'],
            ]);

            SendScheduledCouponDeliveryJob::dispatch($delivery->id)->delay($scheduledAt);

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

        $result = $this->memberAdminService->issueCouponToMembers($projectId, $couponId, $memberIds);

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
        ]);

        return [
            'issued' => $result['count'],
            'scheduled' => false,
            'message' => "{$result['count']}件の会員にクーポンを配布しました",
        ];
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
}
