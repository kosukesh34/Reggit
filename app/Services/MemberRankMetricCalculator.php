<?php

namespace App\Services;

use App\Domain\Settings\Repositories\RankSettings;
use App\Enums\PointTransactionType;
use App\Enums\RankAggregationType;
use App\Models\Member;
use App\Models\MemberStampTransaction;
use App\Models\PointTransaction;
use App\Models\Scopes\StoreAccessScope;
use App\Services\Member\MemberPointLedger;
use Carbon\Carbon;

final class MemberRankMetricCalculator
{
    public function __construct(
        private RankSettings $shopSettingRepository,
        private MemberPointLedger $pointLedger,
    ) {}

    public function points(Member $member, int $projectId): int
    {
        if (! RankAggregationType::isPeriod($this->shopSettingRepository->getRankAggregationType($projectId))) {
            return $this->pointLedger->displayPoints($member);
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
        $sum = (int) PointTransaction::withoutGlobalScope(StoreAccessScope::class)
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
        $sum = (int) MemberStampTransaction::withoutGlobalScope(StoreAccessScope::class)
            ->where('project_id', $projectId)
            ->where('member_id', $member->id)
            ->where('created_at', '>=', $this->periodStartedAt($projectId))
            ->sum('delta');

        return max(0, $sum);
    }
}
