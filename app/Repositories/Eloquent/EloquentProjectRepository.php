<?php

namespace App\Repositories\Eloquent;

use App\Models\Project;
use App\Repositories\Contracts\ProjectRepository;

class EloquentProjectRepository implements ProjectRepository
{
    public function findActiveByProjectKey(string $projectKey): ?Project
    {
        return Project::query()
            ->where('project_key', $projectKey)
            ->where('is_active', true)
            ->where('service_status', 'active')
            ->first();
    }

    public function findFirstActive(): ?Project
    {
        return Project::query()
            ->where('is_active', true)
            ->where('service_status', 'active')
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
            ->where('service_status', 'active')
            ->orderBy('name')
            ->first();
    }
}
