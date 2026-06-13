<?php

namespace App\Jobs;

use App\Enums\CouponDeliveryStatus;
use App\Enums\CouponDeliveryType;
use App\Models\CouponDelivery;
use App\Services\Admin\CouponDeliveryTargetService;
use App\Services\Admin\MemberAdminService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendScheduledCouponDeliveryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $couponDeliveryId
    ) {}

    public function handle(
        MemberAdminService $memberAdminService,
        CouponDeliveryTargetService $targetService,
    ): void {
        $delivery = CouponDelivery::withoutGlobalScopes()->find($this->couponDeliveryId);
        if ($delivery === null || $delivery->status !== CouponDeliveryStatus::SCHEDULED) {
            return;
        }

        try {
            $deliveryType = (string) $delivery->delivery_type;
            $memberIds = is_array($delivery->target_member_ids) ? $delivery->target_member_ids : [];
            if ($memberIds === [] && $delivery->segment_criteria !== null) {
                $resolved = $targetService->resolve(
                    (int) $delivery->project_id,
                    $deliveryType,
                    $targetService->buildResolveParamsFromDelivery($delivery)
                );
                if ($resolved['error'] !== null) {
                    throw new \RuntimeException($resolved['error']);
                }
                $memberIds = $resolved['member_ids'];
            }

            if ($memberIds === [] && ! CouponDeliveryType::isAllMembersDelivery($deliveryType)) {
                $delivery->update([
                    'status' => CouponDeliveryStatus::SENT,
                    'sent_at' => now(),
                    'issued_count' => 0,
                    'target_member_ids' => null,
                ]);

                return;
            }

            $issued = $memberAdminService->issueCouponToMembers(
                (int) $delivery->project_id,
                (int) $delivery->coupon_id,
                $memberIds
            );

            $delivery->update([
                'status' => CouponDeliveryStatus::SENT,
                'sent_at' => now(),
                'issued_count' => $issued,
                'target_member_ids' => $memberIds === [] ? null : $memberIds,
            ]);
        } catch (\Throwable $e) {
            $delivery->update([
                'status' => CouponDeliveryStatus::FAILED,
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
