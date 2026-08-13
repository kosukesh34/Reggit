<?php

namespace App\Models\Concerns;

use App\Models\Scopes\ProjectScope;
use App\Util\AdminContext;

trait BelongsToProject
{
    protected static function bootBelongsToProject(): void
    {
        static::addGlobalScope(new ProjectScope);

        static::creating(function ($model) {
            if ($model->project_id !== null) {
                return;
            }
            $projectId = AdminContext::getCurrentProjectId();
            if ($projectId !== null) {
                $model->project_id = $projectId;
            }
        });
    }
}
