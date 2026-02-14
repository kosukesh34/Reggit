<?php

namespace App\UseCases\Admin\ApiToken;

use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;

class ApiTokenCreateUseCase
{
    /** @return Collection<int, Project> */
    public function handle(): Collection
    {
        return Project::where('is_active', true)->orderBy('name')->get();
    }
}
