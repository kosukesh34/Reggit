<?php

namespace App\Models\Concerns;

trait BelongsToProject
{
    protected static function bootBelongsToProject(): void
    {
        static::creating(function ($model) {
            if ($model->project_id === null) {
                $model->project_id = 1;
            }
        });
    }
}
