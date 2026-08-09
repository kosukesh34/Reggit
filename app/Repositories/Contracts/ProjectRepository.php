<?php

namespace App\Repositories\Contracts;

use App\Models\Project;

interface ProjectRepository
{
    public function findActiveByProjectKey(string $projectKey): ?Project;

    /**
     * スマレジ契約ID（ShopSetting / SmaregiContract）で有効プロジェクトを探す。
     */
    public function findActiveBySmaregiContractId(string $contractId): ?Project;

    public function findFirstActive(): ?Project;

    /**
     * @param  list<int>  $projectIds
     */
    public function findFirstActiveByIds(array $projectIds): ?Project;
}
