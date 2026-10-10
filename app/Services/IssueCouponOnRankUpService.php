<?php

namespace App\Services;

use App\Domain\Settings\Repositories\RankSettings;
use App\Enums\CouponDeliveryStatus;
use App\Enums\CouponDeliveryType;
use App\Enums\MemberCouponSource;
use App\Models\CouponDelivery;
use App\Models\Member;
use App\Services\Admin\MemberBenefitService;
use Illuminate\Support\Facades\DB;

class IssueCouponOnRankUpService
{
    public function __construct(
        private MemberBenefitService $memberBenefitService,
        private RankSettings $shopSettingRepository,
    ) {}

    public function handle(Member $member, string $previousRank, string $newRank, ?int $rankHistoryId = null): void
    {
        if ($previousRank === '' || $previousRank === $newRank || $newRank === '') {
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

            $historyKey = $rankHistoryId !== null ? (string) $rankHistoryId : '0';
            $result = $this->memberBenefitService->issueCouponToMembers(
                (int) $delivery->project_id,
                (int) $delivery->coupon_id,
                [(int) $member->id],
                MemberCouponSource::RANK_UP,
                (string) $delivery->id.':'.$newRank.':'.$historyKey,
                $delivery->send_notification !== false,
            );
            if ($result['count'] < 1) {
                continue;
            }

            DB::transaction(function () use ($delivery, $member): void {
                $current = CouponDelivery::query()
                    ->where('project_id', $delivery->project_id)
                    ->whereKey($delivery->id)
                    ->lockForUpdate()->first();
                if ($current === null) {
                    return;
                }
                $issuedIds = is_array($current->issued_member_ids) ? $current->issued_member_ids : [];
                $issuedIds[] = (int) $member->id;
                $issuedIds = array_values(array_unique(array_map('intval', $issuedIds)));

                $current->update([
                    'issued_member_ids' => $issuedIds,
                    'issued_count' => count($issuedIds),
                ]);
            });
        }
    }

    private function isRankUpgrade(int $projectId, string $previousRank, string $newRank): bool
    {
        $orderByKey = [];
        foreach ($this->shopSettingRepository->getMemberRankSettings($projectId) as $index => $rank) {
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
            return false;
        }

        return $orderByKey[$newRank] > $orderByKey[$previousRank];
    }
}
