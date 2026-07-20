<?php

namespace App\Services\Smaregi;

use App\Models\Project;
use App\Models\Settings\ProjectProfileSmaregiSetting;
use App\Models\SmaregiContract;

/**
 * スマレジ契約 ID から紐づくプロジェクト ID を解決する。
 */
class SmaregiContractProjectResolver
{
    public function __construct(
        private SmaregiApiService $smaregiApi,
    ) {}

    /**
     * @return list<int>
     */
    public function projectIdsForContract(string $contractId): array
    {
        $contractId = trim($contractId);
        if ($contractId === '') {
            return [];
        }

        $fromSettings = ProjectProfileSmaregiSetting::query()
            ->where('smaregi_contract_id', $contractId)
            ->pluck('project_id')
            ->all();
        $fromContracts = SmaregiContract::query()
            ->where('contract_id', $contractId)
            ->whereNotNull('project_id')
            ->pluck('project_id')
            ->all();

        $ids = array_values(array_unique(array_map('intval', array_merge($fromSettings, $fromContracts))));
        if ($ids !== []) {
            return $ids;
        }

        $fromEffectiveContract = [];
        foreach (Project::query()->pluck('id') as $projectId) {
            $effective = $this->smaregiApi->getContractIdForProject((int) $projectId);
            if (is_string($effective) && trim($effective) === $contractId) {
                $fromEffectiveContract[] = (int) $projectId;
            }
        }

        return array_values(array_unique($fromEffectiveContract));
    }
}
