<?php

namespace App\UseCases\AppConfig;

use App\Models\Project;
use App\UseCases\AppConfig\Data\PublicProjectItem;

class ListPublicProjectsUseCase
{
    /**
     * @return list<PublicProjectItem>
     */
    public function handle(): array
    {
        return Project::query()
            ->where('is_active', true)
            ->where('service_status', 'active')
            ->orderBy('name')
            ->get(['project_key', 'name'])
            ->map(static fn (Project $project): PublicProjectItem => new PublicProjectItem(
                (string) $project->project_key,
                (string) $project->name,
            ))
            ->values()
            ->all();
    }
}
