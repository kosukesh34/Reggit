<?php

namespace App\UseCases\Admin\Project;

use App\Models\Project;

class ProjectStoreUseCase
{
    /** @param array<string, string|int|float|bool|array|null> $payload 検証済みプロジェクト作成データ */
    public function handle(array $payload): Project
    {
        return Project::create($payload);
    }
}
