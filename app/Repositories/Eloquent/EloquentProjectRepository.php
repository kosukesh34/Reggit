<?php

namespace App\Repositories\Eloquent;

use App\Models\Project;
use App\Models\Settings\ProjectProfileSmaregiSetting;
use App\Models\SmaregiContract;
use App\Repositories\Contracts\ProjectRepository;

class EloquentProjectRepository implements ProjectRepository
{
    public function findActiveByProjectKey(string $projectKey): ?Project
    {
        return Project::query()
            ->where('project_key', $projectKey)
            ->where('is_active', true)
            ->first();
    }

    public function findActiveBySmaregiContractId(string $contractId): ?Project
    {
        $trimmed = trim($contractId);
        if ($trimmed === '') {
            return null;
        }

        $projectId = ProjectProfileSmaregiSetting::query()
            ->where('smaregi_contract_id', $trimmed)
            ->value('project_id');
        if (! is_numeric($projectId)) {
            $projectId = SmaregiContract::query()
                ->where('contract_id', $trimmed)
                ->whereNotNull('project_id')
                ->value('project_id');
        }
        if (! is_numeric($projectId)) {
            return null;
        }

        return Project::query()
            ->where('id', (int) $projectId)
            ->where('is_active', true)
            ->first();
    }

    public function findFirstActive(): ?Project
    {
        return Project::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
    }

    public function findFirstActiveByIds(array $projectIds): ?Project
    {
        if ($projectIds === []) {
            return null;
        }

        return Project::query()
            ->whereIn('id', $projectIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->first();
    }
}
