<?php

namespace App\UseCases\Admin\Survey;

use App\Domain\Survey\SurveyTemplates;
use App\Models\Survey;
use App\Services\Admin\SurveyAdminService;
use App\UseCases\Admin\Survey\Data\AdminSurveyFormView;

/** 作成画面。雛形を選んだときは、その質問を入れた状態で開く。 */
class AdminSurveyCreateUseCase
{
    public function __construct(private readonly SurveyAdminService $surveys) {}

    public function handle(int $projectId, ?string $templateKey): AdminSurveyFormView
    {
        $template = $templateKey !== null ? SurveyTemplates::find($templateKey) : null;
        $draft = $template === null ? null : new Survey([
            'title' => $template['title'],
            'description' => $template['description'],
            'questions' => $template['questions'],
            'is_active' => true,
        ]);

        return new AdminSurveyFormView(null, false, $draft, SurveyTemplates::summaries(), $this->surveys->tagNames($projectId));
    }
}
