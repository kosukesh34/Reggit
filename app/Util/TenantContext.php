<?php

namespace App\Util;

use App\Models\Project;

/** Request/job-scoped tenant state. Laravel flushes scoped bindings between lifecycles. */
class TenantContext
{
    private ?Project $project = null;

    private bool $requiresProject = false;

    public function setProject(?Project $project): void
    {
        $this->project = $project;
    }

    public function project(): ?Project
    {
        return $this->project;
    }

    public function projectId(): ?int
    {
        return $this->project !== null ? (int) $this->project->id : null;
    }

    public function requireProject(bool $required): void
    {
        $this->requiresProject = $required;
    }

    public function requiresProject(): bool
    {
        return $this->requiresProject;
    }
}
