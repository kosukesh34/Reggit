<?php

namespace App\Services;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Enums\PointTransactionType;
use App\Enums\RankAggregationType;
use App\Models\Member;
use App\Models\MemberStampTransaction;
use App\Models\PointTransaction;
use Carbon\Carbon;

final class MemberRankMetricCalculator
{
    public function __construct(
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    public function points(Member $member, int $projectId): int
    {
        if (! RankAggregationType::isPeriod($this->shopSettingRepository->getRankAggregationType($projectId))) {
            return $member->getDisplayPoints();
        }

        return $this->sumPeriodPoints($member, $projectId);
    }

    public function stamps(Member $member, int $projectId): int
    {
        if (! RankAggregationType::isPeriod($this->shopSettingRepository->getRankAggregationType($projectId))) {
            return $member->getTotalStamps();
        }

        return $this->sumPeriodStamps($member, $projectId);
    }

    public function periodStartedAt(int $projectId): Carbon
    {
        return now()->subDays($this->shopSettingRepository->getRankPeriodDays($projectId))->startOfDay();
    }

    private function sumPeriodPoints(Member $member, int $projectId): int
    {
        $sum = (int) PointTransaction::query()
            ->where('project_id', $projectId)
            ->where('member_id', $member->id)
            ->whereIn('type', [
                PointTransactionType::EARNED,
                PointTransactionType::ADJUSTED,
                PointTransactionType::REVERSED,
            ])
            ->where('created_at', '>=', $this->periodStartedAt($projectId))
            ->sum('points');

        return max(0, $sum);
    }

    private function sumPeriodStamps(Member $member, int $projectId): int
    {
        $sum = (int) MemberStampTransaction::query()
            ->where('project_id', $projectId)
            ->where('member_id', $member->id)
            ->where('created_at', '>=', $this->periodStartedAt($projectId))
            ->sum('delta');

        return max(0, $sum);
    }
}
