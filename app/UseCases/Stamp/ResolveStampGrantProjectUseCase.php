<?php

namespace App\UseCases\Stamp;

use App\Models\Project;
use App\Models\Scopes\ProjectScope;
use App\Models\StampGrantCode;

class ResolveStampGrantProjectUseCase
{
    public function handle(string $token): ?Project
    {
        $projectId = StampGrantCode::query()
            ->withoutGlobalScope(ProjectScope::class)
            ->where('token', $token)
            ->value('project_id');

        if ($projectId === null) {
            return null;
        }

        return Project::query()->find((int) $projectId);
    }
}
