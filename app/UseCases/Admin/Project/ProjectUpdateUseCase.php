<?php

namespace App\UseCases\Admin\Project;

use App\Models\Project;

class ProjectUpdateUseCase
{
    /** @param array<string, mixed> $payload */
    public function handle(int $id, array $payload): Project
    {
        $project = Project::findOrFail($id);
        $project->update($payload);
        return $project;
    }
}
