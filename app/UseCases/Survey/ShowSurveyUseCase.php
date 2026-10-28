<?php

namespace App\UseCases\Survey;

use App\Domain\Settings\Repositories\FeatureSettings;
use App\Domain\Settings\Repositories\OrderSettings;
use App\Domain\Survey\Repositories\SurveyRepository;
use App\Domain\Survey\Repositories\SurveyResponseRepository;
use App\UseCases\Survey\Data\SurveyView;
use Carbon\CarbonImmutable;

/** 回答画面に出すアンケート。受付終了・回答済みも画面で案内するため返す。 */
class ShowSurveyUseCase
{
    public function __construct(
        private readonly SurveyRepository $surveys,
        private readonly SurveyResponseRepository $responses,
        private readonly FeatureSettings $features,
        private readonly OrderSettings $points,
    ) {}

    public function handle(int $projectId, int $surveyId, ?int $memberId): ?SurveyView
    {
        $survey = $this->features->isFeatureEnabled('feature_surveys_enabled', $projectId) ? $this->surveys->find($projectId, $surveyId) : null;
        if ($survey === null) {
            return null;
        }
        $answered = $memberId !== null && $this->responses->isAnswered($survey->id, $memberId);

        return new SurveyView($survey, $survey->isOpen(CarbonImmutable::now()), $answered, $this->points->getUsePoints($projectId) ? $survey->rewardPoints : 0);
    }
}
