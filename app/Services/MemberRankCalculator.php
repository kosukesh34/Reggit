<?php

namespace App\Services;

use App\Domain\Member\Services\MemberRankResolver;
use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Models\Member;
use App\Util\AdminContext;

class MemberRankCalculator
{
    public function __construct(
        private ShopSettingRepository $shopSettingRepository,
        private MemberRankResolver $memberRankResolver,
    ) {}

    /**
     * @return array{current_rank: string, current_rank_label: string, current_rank_icon: string|null, current_rank_background: string|null, next_rank: string|null, next_rank_label: string|null, points_to_next_rank: int|null, rank_basis: string, rank_metric: int}
     */
    public function getRankInfo(Member $member): array
    {
        $projectId = AdminContext::resolveProjectId($member->project_id !== null ? (int) $member->project_id : null);

        return $this->memberRankResolver->resolve(
            $this->shopSettingRepository->getMemberRankSettings((int) $projectId),
            $this->shopSettingRepository->getRankBasis((int) $projectId),
            $member->getDisplayPoints(),
            $member->getTotalStamps(),
            $member->rank,
        );
    }

    public function resolveCurrentRank(Member $member): string
    {
        $info = $this->getRankInfo($member);

        return (string) ($info['current_rank'] ?? $member->rank ?? 'bronze');
    }
}
