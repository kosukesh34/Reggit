<?php

namespace App\UseCases\Admin\Survey;

use App\Services\Admin\SurveyAdminService;

class AdminSurveyDestroyUseCase
{
    public function __construct(private readonly SurveyAdminService $surveys) {}

    public function handle(int $projectId, int $surveyId): void
    {
        $this->surveys->find($projectId, $surveyId)->delete();
    }
}
