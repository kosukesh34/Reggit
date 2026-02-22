<?php

namespace App\Models\Scopes;

use App\Util\AdminContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ProjectScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $projectId = AdminContext::getCurrentProjectId();
        if ($projectId !== null && $projectId !== false) {
            $builder->where($model->getTable() . '.project_id', $projectId);
        }
    }
}
