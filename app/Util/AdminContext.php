<?php

namespace App\Util;

use App\Exceptions\ProjectContextMissingException;
use App\Models\Project;

class AdminContext
{
    public static function setCurrentProject(?Project $project): void
    {
        app(TenantContext::class)->setProject($project);
    }

    public static function getCurrentProject(): ?Project
    {
        return app(TenantContext::class)->project();
    }

    public static function getCurrentProjectId(): ?int
    {
        return app(TenantContext::class)->projectId();
    }

    public static function requireCurrentProjectId(): int
    {
        $projectId = self::getCurrentProjectId();
        if ($projectId === null) {
            throw new ProjectContextMissingException;
        }

        return $projectId;
    }

    public static function resolveProjectId(?int $explicitProjectId): int
    {
        if ($explicitProjectId !== null) {
            return $explicitProjectId;
        }

        return self::requireCurrentProjectId();
    }
}
