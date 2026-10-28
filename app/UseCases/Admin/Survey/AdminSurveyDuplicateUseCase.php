<?php

namespace App\UseCases\Admin\Survey;

use App\Services\Admin\SurveyAdminService;

class AdminSurveyDuplicateUseCase
{
    public function __construct(private readonly SurveyAdminService $surveys) {}

    /** @return int 複製したアンケートのID */
    public function handle(int $projectId, int $surveyId): int
    {
        return (int) $this->surveys->duplicate($this->surveys->find($projectId, $surveyId))->id;
    }
}
