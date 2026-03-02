<?php

namespace App\Util;

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
        return self::$currentProject ? self::$currentProject->id : null;
    }
}
