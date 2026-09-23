<?php

namespace App\Services;

use App\Domain\Member\Services\MemberRankDownGuard;
use App\Domain\Member\Services\MemberRankResolver;
use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Enums\RankAggregationType;
use App\Models\Member;
use App\Util\AdminContext;

class MemberRankCalculator
{
    public function __construct(
        private ShopSettingRepository $shopSettingRepository,
        private MemberRankResolver $memberRankResolver,
        private MemberRankMetricCalculator $memberRankMetricCalculator,
        private MemberRankDownGuard $memberRankDownGuard,
    ) {}

    /**
     * @return array{current_rank: string, current_rank_label: string, current_rank_icon: string|null, current_rank_background: string|null, next_rank: string|null, next_rank_label: string|null, points_to_next_rank: int|null, rank_basis: string, rank_metric: int, rank_aggregation_type: string, days_required_for_next_rank: int|null, days_to_next_rank: int|null}
     */
    public function getRankInfo(Member $member): array
    {
        $projectId = AdminContext::resolveProjectId($member->project_id !== null ? (int) $member->project_id : null);
        $ranks = $this->shopSettingRepository->getMemberRankSettings((int) $projectId);
        $aggregationType = $this->shopSettingRepository->getRankAggregationType((int) $projectId);
        $info = $this->memberRankResolver->resolve(
            $ranks,
            $this->shopSettingRepository->getRankBasis((int) $projectId),
            $this->memberRankMetricCalculator->points($member, (int) $projectId),
            $this->memberRankMetricCalculator->stamps($member, (int) $projectId),
            $member->rank,
        );
        if (RankAggregationType::isPeriod($aggregationType) && ! $this->shopSettingRepository->getIsRankDownEnabled((int) $projectId)) {
            $info = $this->memberRankDownGuard->keepIfHigher(
                $info,
                $ranks,
                is_string($member->rank) ? $member->rank : null,
            );
        }
        $info['rank_aggregation_type'] = $aggregationType;
        $info['days_required_for_next_rank'] = RankAggregationType::isPeriod($aggregationType)
            ? $this->shopSettingRepository->getRankPeriodDays((int) $projectId)
            : null;
        $info['days_to_next_rank'] = null;

        return $info;
    }

    public function resolveCurrentRank(Member $member): string
    {
        $info = $this->getRankInfo($member);

        return (string) ($info['current_rank'] ?? $member->rank ?? 'bronze');
    }
}
