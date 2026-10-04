<?php

namespace App\Repositories\Eloquent;

use App\Models\Settings\ProjectAppLink;

class ProjectAppLinkRepository
{
    public function find(int $projectId): ?ProjectAppLink
    {
        return ProjectAppLink::query()->where('project_id', $projectId)->first();
    }

    /** @param array{download_url: string|null} $urls */
    public function save(int $projectId, array $urls): void
    {
        ProjectAppLink::query()->updateOrCreate(['project_id' => $projectId], $urls);
    }
}
