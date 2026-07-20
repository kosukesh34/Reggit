<?php

namespace App\UseCases\Coupon;

use App\Enums\CouponDeliveryStatus;
use App\Enums\CouponDeliveryType;
use App\Models\CouponDelivery;
use App\Models\Member;
use App\Models\ShopSetting;
use App\Services\Admin\MemberAdminService;

class IssueCouponOnRankUpUseCase
{
    public function __construct(
        private MemberAdminService $memberAdminService,
    ) {}

    public function handle(Member $member, string $previousRank, string $newRank): void
    {
        if ($previousRank === $newRank || $newRank === '') {
            return;
        }

        if ($this->isRankUpgrade((int) $member->project_id, $previousRank, $newRank) === false) {
            return;
        }

        $deliveries = CouponDelivery::query()
            ->where('project_id', $member->project_id)
            ->where('delivery_type', CouponDeliveryType::EVENT_RANK_UP_ARRIVAL)
            ->where('status', CouponDeliveryStatus::ACTIVE)
            ->get();

        foreach ($deliveries as $delivery) {
            $reachRank = trim((string) (($delivery->segment_criteria['rank'] ?? '')));
            if ($reachRank === '' || $reachRank !== $newRank) {
                continue;
            }

            $result = $this->memberAdminService->issueCouponToMembers(
                (int) $delivery->project_id,
                (int) $delivery->coupon_id,
                [(int) $member->id],
            );
            if ($result['count'] < 1) {
                continue;
            }

            $issuedIds = is_array($delivery->issued_member_ids) ? $delivery->issued_member_ids : [];
            $issuedIds[] = (int) $member->id;
            $issuedIds = array_values(array_unique(array_map('intval', $issuedIds)));

            $delivery->update([
                'issued_member_ids' => $issuedIds,
                'issued_count' => count($issuedIds),
            ]);
        }
    }

    private function isRankUpgrade(int $projectId, string $previousRank, string $newRank): bool
    {
        $orderByKey = [];
        foreach (ShopSetting::getMemberRankSettings($projectId) as $index => $rank) {
            if (($rank['is_active'] ?? true) !== true) {
                continue;
            }
            $key = (string) ($rank['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $orderByKey[$key] = (int) ($rank['min_points'] ?? $rank['order'] ?? $index);
        }

        if (! isset($orderByKey[$previousRank], $orderByKey[$newRank])) {
            return $previousRank !== $newRank;
        }

        return $orderByKey[$newRank] > $orderByKey[$previousRank];
    }
}
