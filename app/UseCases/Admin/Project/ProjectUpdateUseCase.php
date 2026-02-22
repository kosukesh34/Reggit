<?php

namespace App\UseCases\Admin\Project;

use App\Models\Project;

class ProjectUpdateUseCase
{
    /** @param array<string, string|int|float|bool|array|null> $payload 検証済みプロジェクト更新データ */
    public function handle(int $id, array $payload): Project
    {
        $project = Project::findOrFail($id);
        $project->update($payload);
        return $project;
    }
}
