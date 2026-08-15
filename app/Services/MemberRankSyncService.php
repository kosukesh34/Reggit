<?php

namespace App\Services;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Models\Member;
use App\Models\MemberRankHistory;
use App\Services\Smaregi\SmaregiCustomerRankAssignmentService;
use App\UseCases\Coupon\IssueCouponOnRankUpUseCase;

class MemberRankSyncService
{
    public function __construct(
        private MemberRankCalculator $rankCalculator,
        private ShopSettingRepository $shopSettingRepository,
        private IssueCouponOnRankUpUseCase $issueCouponOnRankUpUseCase,
        private ScheduledDeliverySendService $scheduledDeliverySendService,
        private SmaregiCustomerRankAssignmentService $smaregiCustomerRankAssignmentService,
    ) {}

    public function sync(Member $member): bool
    {
        $previousRank = (string) ($member->rank ?? '');
        $rankInfo = $this->rankCalculator->getRankInfo($member);
        $newRank = (string) ($rankInfo['current_rank'] ?? '');
        if ($newRank === '' || $newRank === $previousRank) {
            return false;
        }

        $member->update(['rank' => $newRank]);
        $this->recordRankHistory(
            $member,
            $previousRank,
            $newRank,
            $this->resolveRankLabel((int) $member->project_id, $previousRank),
            (string) ($rankInfo['current_rank_label'] ?? $newRank),
        );
        $fresh = $member->fresh() ?? $member;
        $this->issueCouponOnRankUpUseCase->handle($fresh, $previousRank, $newRank);
        $this->scheduledDeliverySendService->sendRankUpArrival($fresh, $newRank);
        $this->smaregiCustomerRankAssignmentService->assignRankToCustomer($fresh, $newRank);

        return true;
    }

    private function recordRankHistory(
        Member $member,
        string $fromRank,
        string $toRank,
        string $fromLabel,
        string $toLabel,
    ): void {
        MemberRankHistory::create([
            'project_id' => (int) $member->project_id,
            'member_id' => (int) $member->id,
            'from_rank' => $fromRank !== '' ? $fromRank : null,
            'to_rank' => $toRank,
            'from_rank_label' => $fromRank !== '' ? $fromLabel : null,
            'to_rank_label' => $toLabel,
            'achieved_at' => now(),
        ]);
    }

    private function resolveRankLabel(int $projectId, string $rankKey): string
    {
        if ($rankKey === '') {
            return '—';
        }
        foreach ($this->shopSettingRepository->getMemberRankSettings($projectId) as $rank) {
            if ((string) ($rank['key'] ?? '') === $rankKey) {
                return (string) ($rank['label'] ?? $rankKey);
            }
        }

        return $rankKey;
    }
}
