<?php

namespace App\UseCases\Admin\Survey;

use App\Domain\Survey\Exceptions\SurveyWithoutQuestions;
use App\Services\Admin\SurveyAdminService;
use App\UseCases\Admin\Survey\Data\SurveyInput;

class AdminSurveyStoreUseCase
{
    public function __construct(private readonly SurveyAdminService $surveys) {}

    /**
     * @return int 作成したアンケートのID
     *
     * @throws SurveyWithoutQuestions
     */
    public function handle(int $projectId, SurveyInput $input): int
    {
        return (int) $this->surveys->create($projectId, $input->settings(), $input->questions)->id;
    }
}
