<?php

namespace App\UseCases\Admin\Survey;

use App\Services\Admin\SurveyAdminService;
use App\UseCases\Admin\Survey\Data\AdminSurveyFormView;

class AdminSurveyEditUseCase
{
    public function __construct(private readonly SurveyAdminService $surveys) {}

    public function handle(int $projectId, int $surveyId): AdminSurveyFormView
    {
        $survey = $this->surveys->find($projectId, $surveyId);

        return new AdminSurveyFormView($survey, $this->surveys->isLocked($survey), null, [], $this->surveys->tagNames($projectId));
    }
}
