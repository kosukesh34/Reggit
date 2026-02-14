<?php

namespace App\UseCases\Admin\Project;

use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProjectIndexUseCase
{
    public function handle(): LengthAwarePaginator
    {
        return Project::orderBy('name')->paginate(20);
    }
}
