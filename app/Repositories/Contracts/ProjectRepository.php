<?php

namespace App\Repositories\Contracts;

use App\Models\Project;

interface ProjectRepository
{
    public function findActiveByProjectKey(string $projectKey): ?Project;

    public function findFirstActive(): ?Project;

    /**
     * @param  list<int>  $projectIds
     */
    public function findFirstActiveByIds(array $projectIds): ?Project;
}
