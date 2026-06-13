<?php

namespace App\Jobs;

use App\Enums\CouponDeliveryStatus;
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
        if (! $delivery || $delivery->status !== CouponDeliveryStatus::SCHEDULED) {
            return;
        }

        try {
            $memberIds = is_array($delivery->target_member_ids) ? $delivery->target_member_ids : [];
            if ($memberIds === [] && $delivery->segment_criteria !== null) {
                $resolved = $targetService->resolve(
                    (int) $delivery->project_id,
                    (string) $delivery->delivery_type,
                    $this->paramsFromCriteria($delivery)
                );
                $memberIds = $resolved['member_ids'];
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

    /** @return array<string, mixed> */
    private function paramsFromCriteria(CouponDelivery $delivery): array
    {
        $criteria = is_array($delivery->segment_criteria) ? $delivery->segment_criteria : [];

        return array_merge($criteria, [
            'target_member_ids' => $delivery->target_member_ids ?? [],
        ]);
    }
}
