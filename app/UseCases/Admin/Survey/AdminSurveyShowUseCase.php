<?php

namespace App\UseCases\Admin\Survey;

use App\Domain\Survey\Policies\SurveyAnswerPolicy;
use App\Domain\Survey\Policies\SurveyResultPolicy;
use App\Services\Admin\SurveyAdminService;
use App\Support\LiffTabUrlBuilder;
use App\UseCases\Admin\Survey\Data\AdminSurveyShowView;

class AdminSurveyShowUseCase
{
    public function __construct(
        private readonly SurveyAdminService $surveys,
        private readonly SurveyResultPolicy $results,
        private readonly LiffTabUrlBuilder $liffTabUrls,
    ) {}

    public function handle(int $projectId, string $projectKey, int $surveyId): AdminSurveyShowView
    {
        $survey = $this->surveys->find($projectId, $surveyId);
        $responses = $this->surveys->answeredResponses($survey);
        $invitations = $this->surveys->invitationCounts($survey);
        $tagIds = array_flip($this->surveys->tagNames($projectId));

        return new AdminSurveyShowView(
            survey: $survey,
            isOpen: $survey->toDefinition()->isOpen(now()),
            responses: $responses,
            stats: $this->results->summarize($survey->toDefinition()->questions, $responses->pluck('answers')->all()),
            invitedCount: $invitations['invited'],
            invitedAnsweredCount: $invitations['answered'],
            shareUrl: $this->liffTabUrls->build($projectId, $projectKey, 'survey', ['survey' => (string) $survey->id]),
            npsTagIds: array_intersect_key($tagIds, array_flip(SurveyAnswerPolicy::NPS_TAGS)),
        );
    }
}
