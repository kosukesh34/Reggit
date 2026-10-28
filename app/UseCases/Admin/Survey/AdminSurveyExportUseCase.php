<?php

namespace App\UseCases\Admin\Survey;

use App\Domain\Survey\Policies\SurveyResultPolicy;
use App\Models\SurveyResponse;
use App\Services\Admin\SurveyAdminService;
use App\UseCases\Admin\Survey\Data\SurveyCsvExport;

/** 回答を表計算ソフトで集計できるよう、1回答1行の CSV にする。 */
class AdminSurveyExportUseCase
{
    public function __construct(
        private readonly SurveyAdminService $surveys,
        private readonly SurveyResultPolicy $results,
    ) {}

    public function handle(int $projectId, int $surveyId): SurveyCsvExport
    {
        $survey = $this->surveys->find($projectId, $surveyId);
        $questions = $survey->toDefinition()->questions;
        $rows = [['回答日時', '会員番号', ...$this->results->csvHeader($questions)]];
        foreach ($this->surveys->answeredResponses($survey) as $response) {
            /** @var SurveyResponse $response */
            $rows[] = [
                (string) $response->answered_at?->format('Y-m-d H:i:s'),
                (string) ($response->member?->member_number ?? '退会済み'),
                ...$this->results->csvRow($questions, $response->answers),
            ];
        }

        return new SurveyCsvExport('survey-'.$survey->id.'-'.now()->format('Ymd').'.csv', $rows);
    }
}
