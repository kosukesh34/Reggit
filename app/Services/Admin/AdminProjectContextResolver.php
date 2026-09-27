<?php

namespace App\Services\Admin;

use App\Models\AdminUser;
use App\Repositories\Contracts\ProjectRepository;
use App\Support\ProjectKey;
use App\Util\AdminContext;
use RuntimeException;

class AdminProjectContextResolver
{
    public function __construct(private ProjectRepository $projects) {}

    public function projectId(): int
    {
        $projectId = AdminContext::getCurrentProjectId();
        if ($projectId !== null) {
            return $projectId;
        }

        return AdminUser::resolveCurrentProjectId(
            auth('admin')->user(),
            (int) session('admin_project_id'),
            request()->filled('project_id') ? (int) request()->input('project_id') : null,
        );
    }

    public function projectKey(): string
    {
        $project = AdminContext::getCurrentProject();
        if ($project !== null && trim((string) $project->project_key) !== '') {
            return (string) $project->project_key;
        }

        $configured = ProjectKey::fromConfig();
        if ($configured !== null) {
            return $configured;
        }

        $project = $this->projects->findFirstActive();
        if ($project === null || trim((string) $project->project_key) === '') {
            throw new RuntimeException('No active project available for admin context.');
        }

        return (string) $project->project_key;
    }
}
