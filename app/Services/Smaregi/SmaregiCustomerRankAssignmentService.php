<?php

namespace App\Services\Smaregi;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Models\Member;
use App\Util\LogHelper;

/**
 * 会員ランクをスマレジ顧客の rank に反映する。
 * ランクマスタ同期（SmaregiCustomerRankSyncService）とは責務を分ける。
 */
class SmaregiCustomerRankAssignmentService
{
    public function __construct(
        private SmaregiApiService $smaregiApi,
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    public function assignRankToCustomer(Member $member, string $rankKey): void
    {
        $smaregiCustomerId = trim((string) ($member->smaregi_customer_id ?? ''));
        if ($smaregiCustomerId === '') {
            return;
        }
        $smaregiRankId = $this->resolveSmaregiRankId((int) $member->project_id, $rankKey);
        if ($smaregiRankId === null) {
            return;
        }
        $contractId = $this->smaregiApi->getContractIdForProject((int) $member->project_id);
        $contractId = is_string($contractId) ? trim($contractId) : '';
        if ($contractId === '') {
            return;
        }

        $this->smaregiApi->clearLastErrorDetail();
        $result = $this->smaregiApi->updateCustomer($contractId, $smaregiCustomerId, [
            'rank' => $smaregiRankId,
        ]);
        if ($result === null) {
            LogHelper::warning('[Member] smaregi customer rank sync failed', [
                'project_id' => (int) $member->project_id,
                'member_id' => (int) $member->id,
                'rank_key' => $rankKey,
                'smaregi_rank_id' => $smaregiRankId,
                'detail' => $this->smaregiApi->getLastErrorDetail(),
            ]);
        }
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
}
