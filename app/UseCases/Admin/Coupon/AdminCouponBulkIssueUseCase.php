<?php

namespace App\UseCases\Admin\Coupon;

use App\Enums\CouponDeliveryStatus;
use App\Enums\CouponDeliveryType;
use App\Jobs\SendScheduledCouponDeliveryJob;
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
        $deliveryType = (string) ($validated['delivery_type'] ?? CouponDeliveryType::IMMEDIATE_ALL);
        $resolved = $this->targetService->resolve($projectId, $deliveryType, $validated);

        if ($resolved['error'] !== null) {
            throw new \InvalidArgumentException($resolved['error']);
        }

        $memberIds = $resolved['member_ids'];
        $isScheduled = CouponDeliveryType::isScheduled($deliveryType);

        if ($isScheduled) {
            $scheduledAt = Carbon::parse((string) $validated['scheduled_at']);
            $delivery = CouponDelivery::create([
                'project_id' => $projectId,
                'coupon_id' => $couponId,
                'delivery_type' => $deliveryType,
                'status' => CouponDeliveryStatus::SCHEDULED,
                'scheduled_at' => $scheduledAt,
                'target_member_ids' => $memberIds === [] ? null : $memberIds,
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

        $issued = $this->memberAdminService->issueCouponToMembers($projectId, $couponId, $memberIds);

        CouponDelivery::create([
            'project_id' => $projectId,
            'coupon_id' => $couponId,
            'delivery_type' => $deliveryType,
            'status' => CouponDeliveryStatus::SENT,
            'sent_at' => now(),
            'target_member_ids' => $memberIds === [] ? null : $memberIds,
            'segment_criteria' => $resolved['segment_criteria'],
            'issued_count' => $issued,
        ]);

        return [
            'issued' => $issued,
            'scheduled' => false,
            'message' => "{$issued}件の会員にクーポンを配布しました",
        ];
    }
}
