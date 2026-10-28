<?php

namespace App\UseCases\Survey;

use App\Domain\Settings\Repositories\FeatureSettings;
use App\Domain\Settings\Repositories\OrderSettings;
use App\Domain\Survey\Repositories\SurveyRepository;
use App\Domain\Survey\Repositories\SurveyResponseRepository;
use App\Domain\Survey\ValueObjects\SurveyDefinition;
use App\UseCases\Survey\Data\SurveyView;
use Carbon\CarbonImmutable;

/** 会員が回答できる（受付期間中の）アンケート一覧。 */
class ListOpenSurveysUseCase
{
    public function __construct(
        private readonly SurveyRepository $surveys,
        private readonly SurveyResponseRepository $responses,
        private readonly FeatureSettings $features,
        private readonly OrderSettings $points,
    ) {}

    /** @return list<SurveyView> */
    public function handle(int $projectId, ?int $memberId): array
    {
        if (! $this->features->isFeatureEnabled('feature_surveys_enabled', $projectId)) {
            return [];
        }
        $now = CarbonImmutable::now();
        $open = array_values(array_filter($this->surveys->findActive($projectId), fn (SurveyDefinition $survey) => $survey->isOpen($now)));
        $answered = $memberId === null ? [] : $this->responses->findAnsweredSurveyIds($memberId, array_map(fn (SurveyDefinition $survey) => $survey->id, $open));

        $usePoints = $this->points->getUsePoints($projectId);

        return array_map(fn (SurveyDefinition $survey) => new SurveyView($survey, true, in_array($survey->id, $answered, true), $usePoints ? $survey->rewardPoints : 0), $open);
    }
}
