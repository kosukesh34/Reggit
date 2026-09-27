<?php

namespace App\Services\Smaregi;

use App\Domain\Settings\Repositories\RankSettings;
use App\Models\Member;
use App\Models\Scopes\ProjectScope;
use App\Util\LogHelper;

/**
 * 会員ランクとランク別ポイント付与率をスマレジ顧客へ反映する。
 * ランクマスタ同期（SmaregiCustomerRankSyncService）とは責務を分ける。
 */
class SmaregiCustomerRankAssignmentService
{
    private const MEMBER_SYNC_CHUNK_SIZE = 50;

    public function __construct(
        private SmaregiApiService $smaregiApi,
        private RankSettings $shopSettingRepository,
    ) {}

    public function assignRankToCustomer(Member $member, string $rankKey): void
    {
        $smaregiCustomerId = trim((string) ($member->smaregi_customer_id ?? ''));
        if ($smaregiCustomerId === '') {
            return;
        }
        $body = $this->buildCustomerRankBody((int) $member->project_id, $rankKey);
        if ($body === []) {
            return;
        }
        $contractId = $this->smaregiApi->getContractIdForProject((int) $member->project_id);
        $contractId = is_string($contractId) ? trim($contractId) : '';
        if ($contractId === '') {
            return;
        }

        $this->smaregiApi->clearLastErrorDetail();
        $result = $this->smaregiApi->updateCustomer($contractId, $smaregiCustomerId, $body);
        if ($result === null) {
            LogHelper::warning('[Member] smaregi customer rank sync failed', [
                'project_id' => (int) $member->project_id,
                'member_id' => (int) $member->id,
                'rank_key' => $rankKey,
                'smaregi_rank_id' => $body['rank'] ?? null,
                'detail' => $this->smaregiApi->getLastErrorDetail(),
            ]);
        }
    }

    /**
     * プロジェクト内のスマレジ連携済み会員へ、現行ランクの付与率を一括反映する。
     */
    public function syncPointRatesForProject(int $projectId): void
    {
        $contractId = $this->smaregiApi->getContractIdForProject($projectId);
        $contractId = is_string($contractId) ? trim($contractId) : '';
        if ($contractId === '') {
            return;
        }

        Member::query()
            ->withoutGlobalScope(ProjectScope::class)
            ->where('project_id', $projectId)
            ->whereNotNull('smaregi_customer_id')
            ->where('smaregi_customer_id', '!=', '')
            ->orderBy('id')
            ->chunkById(self::MEMBER_SYNC_CHUNK_SIZE, function ($members): void {
                foreach ($members as $member) {
                    $rankKey = trim((string) ($member->rank ?? ''));
                    if ($rankKey === '') {
                        continue;
                    }
                    $this->assignRankToCustomer($member, $rankKey);
                }
            });
    }

    /**
     * @return array{rank?: string, pointGivingUnitPrice?: string, pointGivingUnit?: string}
     */
    public function buildCustomerRankBody(int $projectId, string $rankKey): array
    {
        $smaregiRankId = $this->resolveSmaregiRankId($projectId, $rankKey);
        if ($smaregiRankId === null) {
            return [];
        }

        $body = ['rank' => $smaregiRankId];
        $unitPrice = $this->resolvePointGivingUnitPrice($projectId, $rankKey);
        $body['pointGivingUnitPrice'] = (string) $unitPrice;
        $body['pointGivingUnit'] = SmaregiCustomerRankSyncService::POINT_GIVING_UNIT;

        return $body;
    }

    public function resolveSmaregiRankId(int $projectId, string $rankKey): ?string
    {
        if ($rankKey === '') {
            return null;
        }
        foreach ($this->shopSettingRepository->getMemberRankSettings($projectId) as $rank) {
            if ((string) ($rank['key'] ?? '') !== $rankKey) {
                continue;
            }
            $smaregiRankId = isset($rank['smaregi_rank_id']) ? trim((string) $rank['smaregi_rank_id']) : '';

            return $smaregiRankId !== '' ? $smaregiRankId : null;
        }

        return null;
    }

    public function resolvePointGivingUnitPrice(int $projectId, string $rankKey): int
    {
        $rate = $this->shopSettingRepository->getPointEarnRateForRank($projectId, $rankKey);

        return max(1, min(SmaregiCustomerRankSyncService::POINT_GIVING_UNIT_PRICE_MAX, $rate));
    }
}
