<?php

namespace App\Models\Scopes;

use App\Util\AdminContext;
use App\Util\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ProjectScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $projectId = AdminContext::getCurrentProjectId();
        // HTTP requests must not turn a missing tenant into a cross-tenant read.
        // CLI jobs may intentionally query across tenants and retain the legacy
        // behavior until their call sites explicitly remove the scope.
        if ($projectId === null && app(TenantContext::class)->requiresProject()) {
            $builder->whereRaw('1 = 0');

            return;
        }
        if ($projectId !== null) {
            $builder->where($model->getTable().'.project_id', $projectId);
        }
    }
}
