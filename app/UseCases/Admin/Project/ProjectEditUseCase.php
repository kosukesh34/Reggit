<?php

namespace App\UseCases\Admin\Project;

use App\Models\Project;

class ProjectEditUseCase
{
    public function handle(int $id): Project
    {
        return Project::findOrFail($id);
    }
}
