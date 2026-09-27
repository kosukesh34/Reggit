<?php

namespace App\UseCases\Admin\Auth;

use App\Models\AdminUser;
use App\Repositories\Contracts\ProjectRepository;

class ResolveAdminRootProjectUseCase
{
    public function __construct(private ProjectRepository $projects) {}

    public function handle(): ?string
    {
        $admin = auth('admin')->user();
        if (! $admin instanceof AdminUser) {
            return null;
        }

        $project = $this->projects->findFirstActiveByIds($admin->getAllowedProjectIds());

        if ($project === null) {
            $defaultProjectKey = config('app.default_project_key');
            if (is_string($defaultProjectKey) && $defaultProjectKey !== '') {
                return $defaultProjectKey;
            }

            $project = $this->projects->findFirstActive();
        }

        $projectKey = $project?->project_key;

        return is_string($projectKey) && $projectKey !== '' ? $projectKey : null;
    }
}
