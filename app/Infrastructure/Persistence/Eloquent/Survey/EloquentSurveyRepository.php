<?php

namespace App\Infrastructure\Persistence\Eloquent\Survey;

use App\Domain\Survey\Repositories\SurveyRepository;
use App\Domain\Survey\ValueObjects\SurveyDefinition;
use App\Models\Survey;

final class EloquentSurveyRepository implements SurveyRepository
{
    public function find(int $projectId, int $surveyId): ?SurveyDefinition
    {
        $survey = Survey::withoutGlobalScopes()->where('project_id', $projectId)->find($surveyId);

        return $survey === null ? null : $survey->toDefinition();
    }

    public function findActive(int $projectId): array
    {
        return Survey::withoutGlobalScopes()->where('project_id', $projectId)->where('is_active', true)
            ->latest('id')->get()->map(fn (Survey $survey) => $survey->toDefinition())->all();
    }

    public function findActiveWithInvitation(): array
    {
        return Survey::withoutGlobalScopes()->where('is_active', true)->whereNotNull('invite_trigger')
            ->whereIn('project_id', fn ($query) => $query->select('id')->from('projects')->where('is_active', true))
            ->get()->map(fn (Survey $survey) => $survey->toDefinition())->all();
    }
}
