<?php

namespace App\UseCases\Admin\Project;

use App\Models\Project;
use App\Repositories\Eloquent\ProjectAppLinkRepository;

class UpdateProjectAppLinksUseCase
{
    public function __construct(private readonly ProjectAppLinkRepository $repository) {}

    /** @param array{download_url: string|null} $urls */
    public function handle(int $projectId, array $urls): void
    {
        Project::query()->findOrFail($projectId);
        $this->repository->save($projectId, $urls);
    }
}
