<?php

namespace App\Util;

use App\Exceptions\ProjectContextMissingException;
use App\Models\Project;

class AdminContext
{
    private static ?Project $currentProject = null;

    public static function setCurrentProject(?Project $project): void
    {
        self::$currentProject = $project;
    }

    public static function getCurrentProject(): ?Project
    {
        return self::$currentProject;
    }

    public static function getCurrentProjectId(): ?int
    {
        return self::$currentProject !== null ? (int) self::$currentProject->id : null;
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
