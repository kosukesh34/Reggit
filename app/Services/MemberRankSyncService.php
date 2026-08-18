<?php

namespace App\Services;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Models\Member;
use App\Models\MemberRankHistory;
use App\Services\Smaregi\SmaregiCustomerRankAssignmentService;
use Illuminate\Support\Facades\DB;

class MemberRankSyncService
{
    public function __construct(
        private MemberRankCalculator $rankCalculator,
        private ShopSettingRepository $shopSettingRepository,
        private IssueCouponOnRankUpService $issueCouponOnRankUpService,
        private ScheduledDeliverySendService $scheduledDeliverySendService,
        private SmaregiCustomerRankAssignmentService $smaregiCustomerRankAssignmentService,
    ) {}

    public function sync(Member $member): bool
    {
        return (bool) DB::transaction(function () use ($member): bool {
            $locked = Member::query()
                ->withoutGlobalScopes()
                ->whereKey($member->id)
                ->where('project_id', $member->project_id)
                ->lockForUpdate()
                ->first();
            if ($locked === null) {
                return false;
            }

            $previousRank = (string) ($locked->rank ?? '');
            $rankInfo = $this->rankCalculator->getRankInfo($locked);
            $newRank = (string) ($rankInfo['current_rank'] ?? '');
            if ($newRank === '' || $newRank === $previousRank) {
                return false;
            }

            $locked->update(['rank' => $newRank]);
            $history = $this->recordRankHistory(
                $locked,
                $previousRank,
                $newRank,
                $this->resolveRankLabel((int) $locked->project_id, $previousRank),
                (string) ($rankInfo['current_rank_label'] ?? $newRank),
            );
            $fresh = $locked->fresh() ?? $locked;
            $this->issueCouponOnRankUpService->handle($fresh, $previousRank, $newRank, (int) $history->id);
            $this->scheduledDeliverySendService->sendRankUpArrival($fresh, $newRank);
            $this->smaregiCustomerRankAssignmentService->assignRankToCustomer($fresh, $newRank);

            return true;
        });
    }

    private function recordRankHistory(
        Member $member,
        string $fromRank,
        string $toRank,
        string $fromLabel,
        string $toLabel,
    ): MemberRankHistory {
        return MemberRankHistory::create([
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
