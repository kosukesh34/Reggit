<?php

namespace App\UseCases\Legal;

use App\Models\Project;
use App\Repositories\Contracts\ProjectRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GetLegalProjectUseCase
{
    public function __construct(private ProjectRepository $projects) {}

    public function handle(string $projectKey): Project
    {
        return $this->projects->findActiveByProjectKey($projectKey)
            ?? throw new NotFoundHttpException;
    }
}
