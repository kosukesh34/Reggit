<?php

namespace App\Services\Admin;

use App\Domain\Survey\Exceptions\SurveyWithoutQuestions;
use App\Models\MemberTag;
use App\Models\Survey;
use App\Models\SurveyResponse;
use Illuminate\Database\Eloquent\Collection;

/** 管理画面のアンケートの保存・読み取り。 */
class SurveyAdminService
{
    /** @return Collection<int, Survey> 回答数（answered_count）付き */
    public function list(int $projectId): Collection
    {
        return Survey::query()->where('project_id', $projectId)
            ->withCount(['responses as answered_count' => fn ($query) => $query->whereNotNull('answered_at')])
            ->latest('id')->get();
    }

    public function find(int $projectId, int $surveyId): Survey
    {
        return Survey::query()->where('project_id', $projectId)->findOrFail($surveyId);
    }

    /** 回答が入ったアンケートは、集計がずれないよう質問を変更させない */
    public function isLocked(Survey $survey): bool
    {
        return SurveyResponse::query()->where('survey_id', $survey->id)->whereNotNull('answered_at')->exists();
    }

    /**
     * @param  array<string, mixed>  $settings  質問以外の項目
     * @param  list<array<string, mixed>>  $questions
     */
    public function create(int $projectId, array $settings, array $questions): Survey
    {
        if ($questions === []) {
            throw new SurveyWithoutQuestions;
        }

        return Survey::create(['project_id' => $projectId, ...$settings, 'questions' => $questions]);
    }

    /**
     * @param  array<string, mixed>  $settings  質問以外の項目
     * @param  list<array<string, mixed>>  $questions  回答が入ったアンケートでは無視する
     */
    public function update(Survey $survey, array $settings, array $questions): void
    {
        if (! $this->isLocked($survey)) {
            if ($questions === []) {
                throw new SurveyWithoutQuestions;
            }
            $settings['questions'] = $questions;
        }
        $survey->update($settings);
    }

    /** @return Collection<int, SurveyResponse> 回答済みのもの。新しい順 */
    public function answeredResponses(Survey $survey): Collection
    {
        // ponytail: 回答をまとめて読み込んで集計する。数万件になったら SQL 集計に切り替える
        return SurveyResponse::query()->where('survey_id', $survey->id)->whereNotNull('answered_at')
            ->with('member:id,member_number')->latest('answered_at')->get();
    }

    /** @return array{invited: int, answered: int} 自動の回答依頼を送った人数と、そのうち回答した人数 */
    public function invitationCounts(Survey $survey): array
    {
        $invited = SurveyResponse::query()->where('survey_id', $survey->id)->whereNotNull('invited_at');

        return ['invited' => (clone $invited)->count(), 'answered' => $invited->whereNotNull('answered_at')->count()];
    }

    /** 設定をそのまま写したアンケートを、受付を止めた状態で作る */
    public function duplicate(Survey $survey): Survey
    {
        $copy = $survey->replicate(['answered_count']);
        $copy->fill(['title' => mb_substr($survey->title.'（コピー）', 0, 120), 'is_active' => false])->save();

        return $copy;
    }

    /** @return array<int, string> タグID => タグ名 */
    public function tagNames(int $projectId): array
    {
        return MemberTag::query()->where('project_id', $projectId)->orderBy('name')->pluck('name', 'id')->all();
    }
}
