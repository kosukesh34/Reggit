<?php

namespace App\UseCases\Admin\Survey;

use App\Domain\Survey\Exceptions\SurveyWithoutQuestions;
use App\Services\Admin\SurveyAdminService;
use App\UseCases\Admin\Survey\Data\SurveyInput;

class AdminSurveyUpdateUseCase
{
    public function __construct(private readonly SurveyAdminService $surveys) {}

    /** @throws SurveyWithoutQuestions */
    public function handle(int $projectId, int $surveyId, SurveyInput $input): void
    {
        $this->surveys->update($this->surveys->find($projectId, $surveyId), $input->settings(), $input->questions);
    }
}
