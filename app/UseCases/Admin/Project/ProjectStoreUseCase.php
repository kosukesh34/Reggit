<?php

namespace App\UseCases\Admin\Project;

use App\Models\Project;

class ProjectStoreUseCase
{
    /** @param array<string, mixed> $payload */
    public function handle(array $payload): Project
    {
        return Project::create($payload);
    }
}
