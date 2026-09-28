<?php

namespace App\UseCases\Admin\Project;

use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProjectIndexUseCase
{
    public function handle(): LengthAwarePaginator
    {
        return Project::withCount(['stores as active_stores_count' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('name')->paginate(20);
    }
}
