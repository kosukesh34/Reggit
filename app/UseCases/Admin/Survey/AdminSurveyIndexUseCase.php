<?php

namespace App\UseCases\Admin\Survey;

use App\Models\Survey;
use App\Services\Admin\SurveyAdminService;
use Illuminate\Database\Eloquent\Collection;

class AdminSurveyIndexUseCase
{
    public function __construct(private readonly SurveyAdminService $surveys) {}

    /** @return Collection<int, Survey> */
    public function handle(int $projectId): Collection
    {
        return $this->surveys->list($projectId);
    }
}
